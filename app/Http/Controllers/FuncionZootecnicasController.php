<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\FuncionZootecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FuncionZootecnicasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Index', [
            'funciones' => FuncionZootecnica::with('especie')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Create', [
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('funcion_zootecnicas')->where('especie_id', $request->especie_id),
            ],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
        ]);

        FuncionZootecnica::create($validated);

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica creada.');
    }

    public function edit(FuncionZootecnica $funcionZootecnica): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Edit', [
            'funcion' => $funcionZootecnica,
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, FuncionZootecnica $funcionZootecnica): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('funcion_zootecnicas')
                    ->where('especie_id', $request->especie_id)
                    ->ignore($funcionZootecnica->id),
            ],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
        ]);

        $funcionZootecnica->update($validated);

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica actualizada.');
    }

    public function destroy(FuncionZootecnica $funcionZootecnica): RedirectResponse
    {
        $funcionZootecnica->delete();

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica eliminada.');
    }
}
