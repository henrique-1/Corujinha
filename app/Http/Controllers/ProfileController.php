<?php

namespace App\Http\Controllers;

use App\Models\EmailChangeRequest;
use App\Models\TeachingArea;
use App\Notifications\ConfirmCurrentEmailChangeNotification;
use App\Notifications\ConfirmNewEmailNotification;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Exibe a tela de visualização e edição de perfil do usuário.
     */
    public function edit(Request $request, TotpService $totpService): View
    {
        $user = Auth::user();
        $credentials = $user->webauthnCredentials()->latest()->get();

        // Se o usuário ainda não tem TOTP ativo, gera um segredo para configuração
        $pendingSecret = session('totp_pending_secret');
        if (! $user->isTotpEnabled() && ! $pendingSecret) {
            $pendingSecret = $totpService->generateSecret();
            session(['totp_pending_secret' => $pendingSecret]);
        }

        $totpUri = $pendingSecret ? $totpService->getOtpAuthUrl($user->name, $user->email, $pendingSecret) : null;
        $totpQrSvg = $totpUri ? $totpService->generateQrCodeSvg($totpUri) : null;
        $activeTab = $request->query('tab', 'dados');
        $suggestedAreas = TeachingArea::orderBy('name')->pluck('name');

        return view('profile.edit', compact(
            'user',
            'credentials',
            'pendingSecret',
            'totpUri',
            'totpQrSvg',
            'activeTab',
            'suggestedAreas'
        ));
    }

    /**
     * Atualiza dados cadastrais do perfil (Nome, Instituição e Áreas de Atuação).
     * O CPF é um dado institucional protegido e não pode ser alterado.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'institution' => ['nullable', 'string', 'max:256'],
            'teaching_areas' => ['nullable', 'array'],
            'teaching_areas.*' => ['string', 'max:100'],
        ], [
            'name.required' => 'Informe o seu nome completo.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'institution.max' => 'A instituição onde atua deve ter no máximo 256 caracteres.',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => trim($validated['name']),
            'institution' => isset($validated['institution']) && trim($validated['institution']) !== ''
                ? trim($validated['institution'])
                : null,
        ]);

        $rawAreas = (array) $request->input('teaching_areas', []);
        $areaIds = [];
        foreach ($rawAreas as $areaName) {
            $trimmed = trim((string) $areaName);
            if ($trimmed !== '') {
                $teachingArea = TeachingArea::firstOrCreate(['name' => $trimmed]);
                $areaIds[] = $teachingArea->id;
            }
        }
        $user->teachingAreas()->sync($areaIds);

        return redirect()->route('profile.edit')
            ->with('success', 'Dados do perfil atualizados com sucesso!');
    }

    /**
     * Atualiza a foto de perfil (avatar).
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'avatar.required' => 'Selecione uma imagem para a sua foto de perfil.',
            'avatar.image' => 'O arquivo selecionado deve ser uma imagem válida.',
            'avatar.mimes' => 'A imagem deve estar no formato JPG, PNG ou WEBP.',
            'avatar.max' => 'A imagem de perfil deve ter no máximo 4 MB.',
        ]);

        $user = Auth::user();

        // Remove avatar anterior se existir no disco público
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return redirect()->route('profile.edit')
            ->with('success', 'Foto de perfil atualizada com sucesso!');
    }

    /**
     * Remove a foto de perfil e restaura o avatar padrão com iniciais.
     */
    public function destroyAvatar(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => null]);

        return redirect()->route('profile.edit')
            ->with('success', 'Foto de perfil removida com sucesso.');
    }

    /**
     * Etapa 1: Inicia solicitação de alteração de e-mail enviando link ao e-mail atual.
     */
    public function requestEmailChange(): RedirectResponse
    {
        $user = Auth::user();

        // Invalida solicitações anteriores não concluídas
        EmailChangeRequest::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->delete();

        $token1 = Str::random(64);
        $changeRequest = EmailChangeRequest::create([
            'user_id' => $user->id,
            'current_email' => $user->email,
            'step1_token' => $token1,
            'step1_expires_at' => now()->addHour(),
        ]);

        $user->notify(new ConfirmCurrentEmailChangeNotification($changeRequest));

        return redirect()->route('profile.edit')
            ->with('success', "Enviamos um link de confirmação para o seu e-mail atual ({$user->email}). Acesse sua caixa de entrada para prosseguir com a troca.");
    }

    /**
     * Etapa 2: Exibe formulário para o usuário digitar o novo e-mail após clicar no link do e-mail atual.
     */
    public function showEnterNewEmail(string $token): View|RedirectResponse
    {
        $changeRequest = EmailChangeRequest::where('step1_token', $token)->first();

        if (! $changeRequest || ! $changeRequest->isStep1Valid()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Este link de alteração de e-mail expirou ou é inválido. Por favor, solicite uma nova alteração em seu perfil.');
        }

        return view('profile.new-email-form', compact('changeRequest'));
    }

    /**
     * Etapa 3: Recebe o novo e-mail e envia o link de ativação final para o novo endereço.
     */
    public function submitNewEmail(Request $request, string $token): RedirectResponse
    {
        $changeRequest = EmailChangeRequest::where('step1_token', $token)->first();

        if (! $changeRequest || ! $changeRequest->isStep1Valid()) {
            return redirect()->route('profile.edit')
                ->with('error', 'Esta solicitação expirou ou já foi utilizada.');
        }

        $validated = $request->validate([
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                'different:current_email',
            ],
        ], [
            'new_email.required' => 'Informe o novo endereço de e-mail.',
            'new_email.email' => 'Informe um e-mail válido.',
            'new_email.unique' => 'Este e-mail já está sendo utilizado por outro usuário no sistema.',
            'new_email.different' => 'O novo e-mail deve ser diferente do seu e-mail atual.',
        ]);

        $newEmail = mb_strtolower(trim($validated['new_email']));
        $token2 = Str::random(64);

        $changeRequest->update([
            'new_email' => $newEmail,
            'step2_token' => $token2,
            'step2_expires_at' => now()->addHours(24),
        ]);

        Notification::route('mail', $newEmail)
            ->notify(new ConfirmNewEmailNotification($changeRequest));

        return redirect()->route('profile.edit')
            ->with('success', "Quase lá! Enviamos um link de confirmação para o seu novo e-mail ({$newEmail}). Acesse a caixa de entrada para concluir a troca.");
    }

    /**
     * Etapa 4: Conclui a alteração de e-mail ao clicar no link do novo e-mail.
     */
    public function confirmNewEmail(string $token): RedirectResponse
    {
        $changeRequest = EmailChangeRequest::where('step2_token', $token)->first();

        if (! $changeRequest || ! $changeRequest->isStep2Valid()) {
            return redirect()->route('profile.edit')
                ->with('error', 'O link de ativação do novo e-mail é inválido ou expirou. Por favor, solicite novamente a alteração.');
        }

        $user = $changeRequest->user;
        $newEmail = $changeRequest->new_email;

        $user->update([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ]);

        $changeRequest->update([
            'completed_at' => now(),
        ]);

        return redirect()->route('profile.edit')
            ->with('success', "Seu e-mail foi atualizado com sucesso para {$newEmail}!");
    }
}
