<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\FuncionZootecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EspeciesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Especies/Index', [
            'especies' => Especie::withCount('funcionesZootecnicas')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Especies/Create', [
            'funciones' => $this->funciones(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($validated) {
            $especie = Especie::create(['nombre' => $validated['nombre']]);
            $especie->funcionesZootecnicas()->sync($validated['funcion_ids'] ?? []);
        });

        return redirect()->route('especies.index')->with('success', 'Especie creada.');
    }

    public function edit(Especie $especie): Response
    {
        return Inertia::render('Catalogos/Especies/Edit', [
            'especie' => $especie,
            'funcion_ids' => $especie->funcionesZootecnicas()->pluck('funcion_zootecnicas.id'),
            'funciones' => $this->funciones(),
        ]);
    }

    public function update(Request $request, Especie $especie): RedirectResponse
    {
        $validated = $this->validar($request, $especie);

        DB::transaction(function () use ($especie, $validated) {
            $especie->update(['nombre' => $validated['nombre']]);
            $especie->funcionesZootecnicas()->sync($validated['funcion_ids'] ?? []);
        });

        return redirect()->route('especies.index')->with('success', 'Especie actualizada.');
    }

    public function destroy(Especie $especie): RedirectResponse
    {
        $especie->delete();

        return redirect()->route('especies.index')->with('success', 'Especie eliminada.');
    }

    /**
     * @return array{nombre: string, funcion_ids?: list<int>}
     */
    private function validar(Request $request, ?Especie $especie = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:especies,nombre'.($especie ? ','.$especie->id : '')],
            'funcion_ids' => ['nullable', 'array'],
            'funcion_ids.*' => ['integer', 'distinct', 'exists:funcion_zootecnicas,id'],
        ]);
    }

    private function funciones()
    {
        return FuncionZootecnica::orderBy('nombre')->get(['id', 'nombre']);
    }
}
