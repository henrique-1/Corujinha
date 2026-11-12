<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProfessorListUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ProfessorInviteNotification;
use App\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfessorController extends Controller
{
    /**
     * Lista todos os professores cadastrados no sistema.
     */
    public function index(): View
    {
        $professores = User::where('role', 'professor')
            ->latest()
            ->paginate(15);

        return view('admin.professores.index', compact('professores'));
    }

    /**
     * Formulário de cadastro de novo professor pelo Super-Usuário.
     */
    public function create(): View
    {
        return view('admin.professores.create');
    }

    /**
     * Cadastra o professor e dispara o e-mail de convite.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ], [
            'name.required' => 'Informe o nome do professor.',
            'email.required' => 'Informe o e-mail do professor.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
        ]);

        $token = Str::random(64);

        $professor = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'professor',
            'status' => 'pending',
            'invitation_token' => $token,
            'invitation_sent_at' => now(),
        ]);

        $professor->notify(new ProfessorInviteNotification($token));

        BroadcastService::dispatch(new ProfessorListUpdated($professor, 'created'));

        return redirect()->route('admin.professores.index')
            ->with('success', "Convite enviado com sucesso para {$professor->name} ({$professor->email})!");
    }

    /**
     * Reenvia o e-mail de convite para um professor pendente.
     */
    public function resendInvite(User $professor): RedirectResponse
    {
        if ($professor->role !== 'professor' || $professor->isActive()) {
            return back()->with('error', 'Este professor já concluiu o cadastro.');
        }

        $token = Str::random(64);
        $professor->update([
            'invitation_token' => $token,
            'invitation_sent_at' => now(),
        ]);

        $professor->notify(new ProfessorInviteNotification($token));

        BroadcastService::dispatch(new ProfessorListUpdated($professor, 'updated'));

        return back()->with('success', "Novo convite enviado para {$professor->email}!");
    }

    /**
     * Desabilita a autenticação em duas etapas (TOTP) do professor caso ele tenha perdido o autenticador.
     */
    public function disableTotp(User $professor): RedirectResponse
    {
        if ($professor->role !== 'professor') {
            abort(403);
        }

        $professor->update([
            'totp_secret' => null,
            'totp_enabled_at' => null,
            'totp_last_used_slice' => null,
        ]);

        BroadcastService::dispatch(new ProfessorListUpdated($professor, 'updated'));

        return back()->with('success', "O autenticador (TOTP) de {$professor->name} foi desabilitado com sucesso. Ele agora pode entrar apenas com a senha.");
    }

    /**
     * Remove/cancela o convite de um professor pendente.
     */
    public function destroyInvite(User $professor): RedirectResponse
    {
        if ($professor->role !== 'professor') {
            abort(403);
        }

        if ($professor->isActive()) {
            return back()->with('error', 'Não é possível remover o convite de um professor que já ativou sua conta.');
        }

        $email = $professor->email;
        BroadcastService::dispatch(new ProfessorListUpdated($professor, 'deleted'));
        $professor->delete();

        return back()->with('success', "Convite para {$email} foi cancelado e removido com sucesso.");
    }
}
