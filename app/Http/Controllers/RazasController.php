<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\Raza;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RazasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Razas/Index', [
            'razas' => Raza::with('especie')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Razas/Create', [
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Único dentro de la especie: la misma raza puede existir en varias.
            'nombre' => ['required', 'string', 'max:100', Rule::unique('razas')->where('especie_id', $request->input('especie_id'))],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
        ]);

        Raza::create($validated);

        return redirect()->route('razas.index')->with('success', 'Raza creada.');
    }

    public function edit(Raza $raza): Response
    {
        return Inertia::render('Catalogos/Razas/Edit', [
            'raza' => $raza,
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Raza $raza): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('razas')->where('especie_id', $request->input('especie_id'))->ignore($raza->id)],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
        ]);

        $raza->update($validated);

        return redirect()->route('razas.index')->with('success', 'Raza actualizada.');
    }

    public function destroy(Raza $raza): RedirectResponse
    {
        $raza->delete();

        return redirect()->route('razas.index')->with('success', 'Raza eliminada.');
    }
}
