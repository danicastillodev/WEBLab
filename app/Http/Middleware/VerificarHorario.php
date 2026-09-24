<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión del usuario si la petición cae fuera de su ventana de
 * acceso. Se evalúa en cada request, así que un usuario que ya estaba dentro
 * es expulsado en cuanto termina su horario.
 */
class VerificarHorario
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->dentroDeHorario()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Su acceso al sistema está fuera del horario permitido.',
            ]);
        }

        return $next($request);
    }
}
