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
    public const MENSAJE = 'Su acceso al sistema está fuera del horario permitido.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->dentroDeHorario()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // La pantalla de login muestra los errores bajo el campo "username".
            return redirect()->route('login')->withErrors([
                'username' => self::MENSAJE,
            ]);
        }

        return $next($request);
    }
}
