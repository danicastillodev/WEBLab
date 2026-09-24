<?php

namespace App\Http\Controllers;

use App\Models\DatosGeneral;
use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatosGeneralesController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Configuracion/DatosGenerales/Edit', [
            'datosGenerales' => DatosGeneral::first(),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_laboratorio' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:150'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'telefono' => ['nullable', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'jefe_laboratorio' => ['required', 'string', 'max:150'],
        ], [
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        $registro = DatosGeneral::first();

        if ($registro) {
            $registro->update($validated);
        } else {
            DatosGeneral::create($validated);
        }

        return redirect()->route('datos-generales.edit')->with('success', 'Datos generales actualizados.');
    }
}
