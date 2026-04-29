<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MunicipiosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Municipios/Index', [
            'municipios' => Municipio::with('estado')
                ->orderBy('nombre')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Municipios/Create', [
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:150'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
        ]);

        $request->validate([
            'nombre' => [
                \Illuminate\Validation\Rule::unique('municipios')->where('estado_id', $validated['estado_id']),
            ],
        ], ['nombre.unique' => 'Ya existe ese municipio en el estado seleccionado.']);

        Municipio::create($validated);

        return redirect()->route('municipios.index')->with('success', 'Municipio creado.');
    }

    public function edit(Municipio $municipio): Response
    {
        return Inertia::render('Catalogos/Municipios/Edit', [
            'municipio' => $municipio->load('estado'),
            'estados'   => Estado::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Municipio $municipio): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:150'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
        ]);

        $request->validate([
            'nombre' => [
                \Illuminate\Validation\Rule::unique('municipios')->where('estado_id', $validated['estado_id'])->ignore($municipio->id),
            ],
        ], ['nombre.unique' => 'Ya existe ese municipio en el estado seleccionado.']);

        $municipio->update($validated);

        return redirect()->route('municipios.index')->with('success', 'Municipio actualizado.');
    }

    public function destroy(Municipio $municipio): RedirectResponse
    {
        $municipio->delete();

        return redirect()->route('municipios.index')->with('success', 'Municipio eliminado.');
    }
}
