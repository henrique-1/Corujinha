<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ConfirmIdentityController extends Controller
{
    /**
     * Exibe a tela de confirmação recente de identidade (step-up authentication).
     */
    public function show(): View
    {
        return view('auth.confirm-identity', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Valida a senha atual e, caso o TOTP esteja ativo na conta, o código TOTP de 6 dígitos.
     */
    public function store(Request $request, TotpService $totpService): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'string'],
        ];

        if ($user->isTotpEnabled()) {
            $rules['totp_code'] = ['required', 'string', 'size:6'];
        }

        $request->validate($rules, [
            'password.required' => 'Informe sua senha atual para confirmar sua identidade.',
            'totp_code.required' => 'Informe o código de 6 dígitos do seu aplicativo autenticador.',
            'totp_code.size' => 'O código TOTP deve ter 6 dígitos.',
        ]);

        if (! Hash::check((string) $request->input('password'), (string) $user->password)) {
            return back()->withErrors([
                'password' => 'A senha informada está incorreta.',
            ]);
        }

        if ($user->isTotpEnabled() && ! $totpService->verifyOnce($user, (string) $request->input('totp_code'))) {
            return back()->withErrors([
                'totp_code' => 'Código TOTP inválido, expirado ou já utilizado. Aguarde o próximo código de 6 dígitos.',
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('security.index'))
            ->with('success', 'Identidade confirmada com sucesso.');
    }
}
