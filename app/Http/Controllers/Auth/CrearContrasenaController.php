<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Primer inicio de sesión de un usuario sin contraseña (recién dado de alta o
 * al que se le borró desde "Modificar usuario"): AuthenticatedSessionController
 * guarda su id en la sesión y aquí la crea, queda asignada y entra al sistema.
 */
class CrearContrasenaController extends Controller
{
    public const SESION = 'crear_contrasena.user_id';

    public function create(Request $request): Response|RedirectResponse
    {
        $usuario = $this->usuarioPendiente($request);

        if (! $usuario) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/CrearContrasena', [
            'nombre' => $usuario->name,
            'username' => $usuario->username,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $this->usuarioPendiente($request);

        // Ya no está pendiente (la creó en otra pestaña o la sesión expiró).
        if (! $usuario) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $usuario->forceFill(['password' => Hash::make($request->input('password'))])->save();

        $request->session()->forget(self::SESION);
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function usuarioPendiente(Request $request): ?User
    {
        $id = $request->session()->get(self::SESION);

        return $id ? User::whereKey($id)->whereNull('password')->first() : null;
    }
}
