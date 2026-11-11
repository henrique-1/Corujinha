<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * Exibe o formulário de redefinição de senha com base no token fornecido.
     */
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Redefine a senha do usuário aplicando as regras da NIST SP 800-63B-4.
     */
    public function reset(Request $request): RedirectResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(15)->max(64),
                function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                    if (! $user) {
                        return;
                    }

                    $lowerValue = mb_strtolower((string) $value);
                    $lowerName = mb_strtolower($user->name);
                    $emailUser = mb_strtolower(explode('@', $user->email)[0] ?? '');

                    if (! empty($lowerName) && str_contains($lowerValue, $lowerName)) {
                        $fail('A senha não deve conter seu nome.');
                    }

                    if (! empty($emailUser) && strlen($emailUser) >= 3 && str_contains($lowerValue, $emailUser)) {
                        $fail('A senha não deve conter o prefixo do seu e-mail.');
                    }

                    if (! empty($user->cpf) && str_contains($lowerValue, $user->cpf)) {
                        $fail('A senha não deve conter seu CPF.');
                    }
                },
            ],
        ], [
            'email.required' => 'Informe o seu e-mail.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.min' => 'A senha deve possuir no mínimo 15 caracteres (NIST SP 800-63B-4).',
            'password.max' => 'A senha deve possuir no máximo 64 caracteres (NIST SP 800-63B-4).',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Sua senha foi redefinida com sucesso! Você já pode fazer login com sua nova credencial.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
