<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Personas/Index', [
            'personas' => Persona::withCount('direcciones')
                ->orderBy('apellidos')
                ->orderBy('nombre')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Personas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['required', 'string', 'size:18', 'unique:personas,curp'],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'unique:personas,rfc'],
            'direcciones' => ['required', 'array', 'min:1'],
            'direcciones.*.calle' => ['required', 'string', 'max:200'],
            'direcciones.*.numero_exterior' => ['required', 'string', 'max:20'],
            'direcciones.*.numero_interior' => ['nullable', 'string', 'max:20'],
            'direcciones.*.colonia' => ['required', 'string', 'max:150'],
            'direcciones.*.municipio' => ['required', 'string', 'max:150'],
            'direcciones.*.estado' => ['required', 'string', 'max:100'],
            'direcciones.*.codigo_postal' => ['required', 'string', 'max:10'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe una persona con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe una persona con ese RFC.',
            'direcciones.min' => 'Se requiere al menos una dirección.',
        ]);

        $persona = Persona::create([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => strtoupper($validated['curp']),
            'rfc' => strtoupper($validated['rfc']),
        ]);

        foreach ($validated['direcciones'] as $dir) {
            $persona->direcciones()->create($dir);
        }

        return redirect()->route('personas.index')->with('success', 'Persona creada.');
    }

    public function edit(Persona $persona): Response
    {
        return Inertia::render('Catalogos/Personas/Edit', [
            'persona' => $persona->load('direcciones'),
        ]);
    }

    public function update(Request $request, Persona $persona): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['required', 'string', 'size:18', 'unique:personas,curp,'.$persona->id],
            'rfc' => ['required', 'string', 'min:12', 'max:13', 'unique:personas,rfc,'.$persona->id],
            'direcciones' => ['required', 'array', 'min:1'],
            'direcciones.*.calle' => ['required', 'string', 'max:200'],
            'direcciones.*.numero_exterior' => ['required', 'string', 'max:20'],
            'direcciones.*.numero_interior' => ['nullable', 'string', 'max:20'],
            'direcciones.*.colonia' => ['required', 'string', 'max:150'],
            'direcciones.*.municipio' => ['required', 'string', 'max:150'],
            'direcciones.*.estado' => ['required', 'string', 'max:100'],
            'direcciones.*.codigo_postal' => ['required', 'string', 'max:10'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe una persona con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe una persona con ese RFC.',
            'direcciones.min' => 'Se requiere al menos una dirección.',
        ]);

        $persona->update([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => strtoupper($validated['curp']),
            'rfc' => strtoupper($validated['rfc']),
        ]);

        $persona->direcciones()->delete();

        foreach ($validated['direcciones'] as $dir) {
            $persona->direcciones()->create($dir);
        }

        return redirect()->route('personas.index')->with('success', 'Persona actualizada.');
    }

    public function destroy(Persona $persona): RedirectResponse
    {
        $persona->delete();

        return redirect()->route('personas.index')->with('success', 'Persona eliminada.');
    }
}
