<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EspeciesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Especies/Index', [
            'especies' => Especie::orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Especies/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:especies'],
        ]);

        Especie::create($validated);

        return redirect()->route('especies.index')->with('success', 'Especie creada.');
    }

    public function edit(Especie $especie): Response
    {
        return Inertia::render('Catalogos/Especies/Edit', [
            'especie' => $especie,
        ]);
    }

    public function update(Request $request, Especie $especie): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:especies,nombre,'.$especie->id],
        ]);

        $especie->update($validated);

        return redirect()->route('especies.index')->with('success', 'Especie actualizada.');
    }

    public function destroy(Especie $especie): RedirectResponse
    {
        $especie->delete();

        return redirect()->route('especies.index')->with('success', 'Especie eliminada.');
    }
}
