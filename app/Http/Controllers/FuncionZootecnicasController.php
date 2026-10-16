<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\FuncionZootecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FuncionZootecnicasController extends Controller
{
    public function index(Request $request): Response
    {
        $buscar = trim((string) $request->query('buscar', ''));

        return Inertia::render('Catalogos/FuncionZootecnicas/Index', [
            'funciones' => FuncionZootecnica::with(['especies' => fn ($q) => $q->orderBy('nombre')->select('especies.id', 'especies.nombre')])
                ->when($buscar !== '', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%"))
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
            'buscar' => $buscar,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Create', [
            'especies' => $this->especies(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($validated) {
            $funcion = FuncionZootecnica::create(['nombre' => $validated['nombre']]);
            $funcion->especies()->sync($validated['especie_ids'] ?? []);
        });

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica creada.');
    }

    public function edit(FuncionZootecnica $funcionZootecnica): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Edit', [
            'funcion' => $funcionZootecnica,
            'especie_ids' => $funcionZootecnica->especies()->pluck('especies.id'),
            'especies' => $this->especies(),
        ]);
    }

    public function update(Request $request, FuncionZootecnica $funcionZootecnica): RedirectResponse
    {
        $validated = $this->validar($request, $funcionZootecnica);

        DB::transaction(function () use ($funcionZootecnica, $validated) {
            $funcionZootecnica->update(['nombre' => $validated['nombre']]);
            $funcionZootecnica->especies()->sync($validated['especie_ids'] ?? []);
        });

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica actualizada.');
    }

    public function destroy(FuncionZootecnica $funcionZootecnica): RedirectResponse
    {
        $funcionZootecnica->delete();

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica eliminada.');
    }

    /**
     * @return array{nombre: string, especie_ids?: list<int>}
     */
    private function validar(Request $request, ?FuncionZootecnica $funcion = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('funcion_zootecnicas')->ignore($funcion?->id)],
            'especie_ids' => ['nullable', 'array'],
            'especie_ids.*' => ['integer', 'distinct', 'exists:especies,id'],
        ]);
    }

    private function especies()
    {
        return Especie::orderBy('nombre')->get(['id', 'nombre']);
    }
}
