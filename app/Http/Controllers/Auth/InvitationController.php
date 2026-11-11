<?php

namespace App\Http\Controllers\Auth;

use App\Events\ProfessorListUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Exibe o formulário para o professor finalizar seu cadastro.
     */
    public function show(string $token): View|RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Link de convite inválido ou já utilizado.',
            ]);
        }

        return view('auth.accept-invitation', compact('user', 'token'));
    }

    /**
     * Processa a finalização do cadastro pelo professor.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Link de convite inválido ou expirado.',
            ]);
        }

        $sanitizedCpf = CpfRule::sanitize($request->input('cpf'));

        $request->merge(['cpf_sanitized' => $sanitizedCpf]);

        $request->validate([
            'cpf' => ['required', 'string', new CpfRule],
            'cpf_sanitized' => ['unique:users,cpf,'.$user->id],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(15)->max(64),
                function (string $attribute, mixed $value, \Closure $fail) use ($user, $sanitizedCpf) {
                    $lowerValue = mb_strtolower((string) $value);
                    $lowerName = mb_strtolower($user->name);
                    $emailUser = mb_strtolower(explode('@', $user->email)[0] ?? '');

                    if (! empty($lowerName) && str_contains($lowerValue, $lowerName)) {
                        $fail('A senha não deve conter seu nome.');
                    }

                    if (! empty($emailUser) && strlen($emailUser) >= 3 && str_contains($lowerValue, $emailUser)) {
                        $fail('A senha não deve conter o prefixo do seu e-mail.');
                    }

                    if (! empty($sanitizedCpf) && str_contains($lowerValue, $sanitizedCpf)) {
                        $fail('A senha não deve conter seu CPF.');
                    }
                },
            ],
        ], [
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf_sanitized.unique' => 'Este CPF já está em uso por outro usuário.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve possuir no mínimo 15 caracteres (NIST SP 800-63B-4).',
            'password.max' => 'A senha deve possuir no máximo 64 caracteres (NIST SP 800-63B-4).',
        ]);

        $user->update([
            'cpf' => $sanitizedCpf,
            'password' => Hash::make($request->input('password')),
            'status' => 'active',
            'invitation_token' => null,
            'email_verified_at' => now(),
        ]);

        broadcast(new ProfessorListUpdated($user, 'updated'));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('security.passkey.suggestion')
            ->with('success', 'Cadastro finalizado com sucesso! Bem-vindo ao Corujinha AVALIA+.');
    }
}
