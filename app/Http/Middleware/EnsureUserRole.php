<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Sua conta ainda não foi ativada. Por favor, utilize o link enviado ao seu e-mail para finalizar o cadastro.',
            ]);
        }

        // Se roles for vazio, apenas checa se é usuário autenticado e ativo
        if (empty($roles)) {
            return $next($request);
        }

        // Super-admin sempre tem acesso a tudo
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Acesso não autorizado para o seu perfil de usuário.');
    }
}
