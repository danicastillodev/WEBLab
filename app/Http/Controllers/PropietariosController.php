<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Propietario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropietariosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Propietarios/Index', [
            'propietarios' => Propietario::withCount('direcciones')
                ->orderBy('apellidos')
                ->orderBy('nombre')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Propietarios/Create', [
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['nullable', 'string', 'size:18', 'unique:propietarios,curp'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'unique:propietarios,rfc'],
            'telefono' => ['nullable', 'digits:10'],
            'direcciones' => ['required', 'array', 'min:1'],
            'direcciones.*.calle' => ['required', 'string', 'max:200'],
            'direcciones.*.numero_exterior' => ['required', 'string', 'max:20'],
            'direcciones.*.numero_interior' => ['nullable', 'string', 'max:20'],
            'direcciones.*.colonia' => ['required', 'string', 'max:150'],
            'direcciones.*.estado_id' => ['required', 'integer', 'exists:estados,id'],
            'direcciones.*.municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'direcciones.*.codigo_postal' => ['required', 'string', 'max:10'],
            'direcciones.*.caseta' => ['nullable', 'string', 'max:50'],
            'direcciones.*.lote' => ['nullable', 'string', 'max:50'],
            'direcciones.*.parvada' => ['nullable', 'string', 'max:50'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un propietario con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'direcciones.min' => 'Se requiere al menos una dirección.',
        ]);

        $propietario = Propietario::create([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => isset($validated['curp']) ? strtoupper($validated['curp']) : null,
            'rfc' => isset($validated['rfc']) ? strtoupper($validated['rfc']) : null,
            'telefono' => $validated['telefono'] ?? null,
        ]);

        foreach ($validated['direcciones'] as $dir) {
            $propietario->direcciones()->create($dir);
        }

        return redirect()->route('propietarios.index')->with('success', 'Propietario creado.');
    }

    public function edit(Propietario $propietario): Response
    {
        return Inertia::render('Catalogos/Propietarios/Edit', [
            'propietario' => $propietario->load('direcciones'),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function update(Request $request, Propietario $propietario): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['nullable', 'string', 'size:18', 'unique:propietarios,curp,'.$propietario->id],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'unique:propietarios,rfc,'.$propietario->id],
            'telefono' => ['nullable', 'digits:10'],
            'direcciones' => ['required', 'array', 'min:1'],
            'direcciones.*.calle' => ['required', 'string', 'max:200'],
            'direcciones.*.numero_exterior' => ['required', 'string', 'max:20'],
            'direcciones.*.numero_interior' => ['nullable', 'string', 'max:20'],
            'direcciones.*.colonia' => ['required', 'string', 'max:150'],
            'direcciones.*.estado_id' => ['required', 'integer', 'exists:estados,id'],
            'direcciones.*.municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'direcciones.*.codigo_postal' => ['required', 'string', 'max:10'],
            'direcciones.*.caseta' => ['nullable', 'string', 'max:50'],
            'direcciones.*.lote' => ['nullable', 'string', 'max:50'],
            'direcciones.*.parvada' => ['nullable', 'string', 'max:50'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un propietario con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'direcciones.min' => 'Se requiere al menos una dirección.',
        ]);

        $propietario->update([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => isset($validated['curp']) ? strtoupper($validated['curp']) : null,
            'rfc' => isset($validated['rfc']) ? strtoupper($validated['rfc']) : null,
            'telefono' => $validated['telefono'] ?? null,
        ]);

        $propietario->direcciones()->delete();

        foreach ($validated['direcciones'] as $dir) {
            $propietario->direcciones()->create($dir);
        }

        return redirect()->route('propietarios.index')->with('success', 'Propietario actualizado.');
    }

    public function destroy(Propietario $propietario): RedirectResponse
    {
        $propietario->delete();

        return redirect()->route('propietarios.index')->with('success', 'Propietario eliminado.');
    }
}
