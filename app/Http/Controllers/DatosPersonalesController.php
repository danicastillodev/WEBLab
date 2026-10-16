<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Permite a cualquier usuario autenticado cambiar su propio nombre, correo y
 * contraseña desde el menú Seguridad. No depende de permisos: cada quien sólo
 * puede modificar su propia cuenta.
 */
class DatosPersonalesController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Seguridad/DatosPersonales/Edit');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated())->save();

        return redirect()->route('datos-personales.edit')
            ->with('success', 'Datos personales actualizados.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [], [
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('datos-personales.edit')
            ->with('success', 'Contraseña actualizada.');
    }
}
