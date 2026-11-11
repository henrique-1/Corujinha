<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasskeyService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use RuntimeException;

class AuthController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Processa a autenticação com e-mail e senha.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], (string) $user->password)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'As credenciais informadas não correspondem aos nossos registros.',
            ]);
        }

        if (! $user->isActive()) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Sua conta ainda não foi ativada. Verifique seu e-mail para concluir o cadastro.',
            ]);
        }

        // Se TOTP estiver ativo, direciona para o desafio de dois fatores
        if ($user->isTotpEnabled()) {
            $request->session()->put('totp_auth_user_id', $user->id);
            $request->session()->put('totp_remember', $request->boolean('remember'));

            return redirect()->route('login.totp');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Exibe a tela do desafio TOTP.
     */
    public function showTotpChallenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('totp_auth_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.totp-challenge');
    }

    /**
     * Valida o código TOTP no login.
     */
    public function verifyTotp(Request $request, TotpService $totpService): RedirectResponse
    {
        $userId = $request->session()->get('totp_auth_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Informe o código do aplicativo autenticador.',
            'code.size' => 'O código deve ter 6 dígitos.',
        ]);

        $user = User::findOrFail($userId);

        if (! $totpService->verify((string) $user->totp_secret, $request->input('code'))) {
            return back()->withErrors([
                'code' => 'Código de autenticação inválido ou expirado. Tente novamente.',
            ]);
        }

        $remember = (bool) $request->session()->get('totp_remember', false);
        $request->session()->forget(['totp_auth_user_id', 'totp_remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->redirectBasedOnRole($user);
    }

    /**
     * Gera as opções WebAuthn para autenticação com Passkey.
     */
    public function passkeyLoginOptions(Request $request, PasskeyService $passkeyService): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'email'],
        ]);

        return response()->json(
            $passkeyService->generateAuthenticationOptions($validated['email'] ?? null, $request)
        );
    }

    /**
     * Valida a asserção WebAuthn e autentica diretamente o usuário sem exigir TOTP.
     */
    public function passkeyLoginVerify(Request $request, PasskeyService $passkeyService): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string'],
            'clientDataJSON' => ['required', 'string'],
            'authenticatorData' => ['required', 'string'],
            'signature' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        try {
            $credential = $passkeyService->verifyAssertion($validated, $request);
            $user = $credential->user;

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'redirect_url' => $this->dashboardUrlForUser($user),
            ]);
        } catch (RuntimeException|\InvalidArgumentException $e) {
            if ($e->getMessage() === 'CREDENTIAL_NOT_FOUND') {
                return response()->json([
                    'error' => 'Passkey não reconhecida neste sistema. Faça login com e-mail e senha ou cadastre-a novamente.',
                    'code' => 'CREDENTIAL_NOT_FOUND',
                ], 404);
            }

            if ($e->getMessage() === 'LEGACY_PASSKEY') {
                return response()->json([
                    'error' => 'Esta Passkey foi registrada em uma versão anterior sem chave pública válida. Entre com e-mail e senha e cadastre-a novamente na Central de Segurança.',
                ], 422);
            }

            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Realiza o logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Você saiu da sua conta.');
    }

    /**
     * Redireciona o usuário para o painel correspondente ao seu papel.
     */
    private function redirectBasedOnRole(User $user): RedirectResponse
    {
        return redirect()->to($this->dashboardUrlForUser($user));
    }

    /**
     * Retorna a URL do painel inicial conforme o papel do usuário.
     */
    private function dashboardUrlForUser(User $user): string
    {
        if ($user->isSuperAdmin()) {
            return route('admin.professores.index');
        }

        return route('professor.salas.index');
    }
}
