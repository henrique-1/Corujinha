<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WebauthnCredential;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SecurityController extends Controller
{
    /**
     * Tela de sugestão de ativação de Passkey após finalizar o cadastro.
     */
    public function passkeySuggestion(): View
    {
        return view('auth.passkey-suggestion');
    }

    /**
     * Central de Segurança do Usuário (TOTP e Passkeys).
     */
    public function index(TotpService $totpService): View
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

        return view('auth.security', compact('user', 'credentials', 'pendingSecret', 'totpUri'));
    }

    /**
     * Habilita o TOTP após conferência de código digitado.
     */
    public function enableTotp(Request $request, TotpService $totpService): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Informe o código do aplicativo autenticador.',
            'code.size' => 'O código deve ter 6 dígitos.',
        ]);

        $pendingSecret = session('totp_pending_secret');
        if (! $pendingSecret) {
            return back()->withErrors(['code' => 'Sessão de configuração expirada. Recarregue a página.']);
        }

        if (! $totpService->verify($pendingSecret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Código de autenticação incorreto. Verifique o horário do seu celular.']);
        }

        $user = Auth::user();
        $user->update([
            'totp_secret' => $pendingSecret,
            'totp_enabled_at' => now(),
        ]);

        session()->forget('totp_pending_secret');

        return redirect()->route('security.index')->with('success', 'Autenticação em duas etapas (TOTP) ativada com sucesso!');
    }

    /**
     * Desabilita o TOTP pelo próprio usuário.
     */
    public function disableTotp(): RedirectResponse
    {
        $user = Auth::user();
        $user->update([
            'totp_secret' => null,
            'totp_enabled_at' => null,
        ]);

        return redirect()->route('security.index')->with('success', 'Autenticação em duas etapas (TOTP) desativada.');
    }

    /**
     * Opções de desafio WebAuthn para registrar uma nova Passkey.
     */
    public function passkeyRegisterOptions(): JsonResponse
    {
        $user = Auth::user();
        $challenge = bin2hex(random_bytes(32));
        session(['webauthn_register_challenge' => $challenge]);

        $existing = $user->webauthnCredentials->pluck('credential_id')->map(function ($id) {
            return [
                'type' => 'public-key',
                'id' => $id,
            ];
        })->values()->all();

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => config('app.name', 'Corujinha AVALIA+'),
                'id' => request()->getHost(),
            ],
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $existing,
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey' => 'preferred',
            ],
        ]);
    }

    /**
     * Salva uma Passkey registrada.
     */
    public function passkeyRegisterStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = Auth::user();

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $validated['id'],
            'public_key' => $validated['rawId'],
            'name' => $validated['name'] ?: 'Minha Passkey',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove uma Passkey.
     */
    public function passkeyDestroy(WebauthnCredential $credential): RedirectResponse
    {
        if ($credential->user_id !== Auth::id()) {
            abort(403);
        }

        $credential->delete();

        return back()->with('success', 'Passkey removida com sucesso.');
    }
}
