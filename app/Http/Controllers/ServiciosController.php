<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiciosController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'area_id', 'metodo']);

        $servicios = Servicio::with('area')
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query->where('clave', 'like', "%{$buscar}%")
                        ->orWhere('siglas', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['area_id'] ?? null, fn ($query, $areaId) => $query->where('area_id', $areaId))
            ->when($filtros['metodo'] ?? null, fn ($query, $metodo) => $query->where('metodo', $metodo))
            ->orderBy('clave')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogos/Servicios/Index', [
            'servicios' => $servicios,
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
            'filtros' => $filtros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Servicios/Create', [
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Servicio::create($this->validado($request));

        return redirect()->route('servicios.index')->with('success', 'Servicio creado.');
    }

    public function edit(Servicio $servicio): Response
    {
        return Inertia::render('Catalogos/Servicios/Edit', [
            'servicio' => $servicio,
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $servicio->update($this->validado($request, $servicio));

        return redirect()->route('servicios.index')->with('success', 'Servicio actualizado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        $servicio->delete();

        return redirect()->route('servicios.index')->with('success', 'Servicio eliminado.');
    }

    private function validado(Request $request, ?Servicio $servicio = null): array
    {
        return $request->validate([
            'clave' => ['required', 'string', 'max:20', Rule::unique('servicios')->ignore($servicio)],
            'siglas' => ['nullable', 'string', 'max:15'],
            'descripcion' => ['required', 'string', 'max:255'],
            'metodo' => ['nullable', 'string', 'max:100'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'referencias' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ]);
    }
}
