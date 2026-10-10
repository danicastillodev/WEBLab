<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\Producto;
use App\Models\Prueba;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PruebasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Pruebas/Index', [
            'pruebas' => Prueba::with(['especie', 'producto'])->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Pruebas/Create', [
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'precio_unitario']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
        ]);

        Prueba::create($validated);

        return redirect()->route('pruebas.index')->with('success', 'Prueba creada.');
    }

    public function edit(Prueba $prueba): Response
    {
        return Inertia::render('Catalogos/Pruebas/Edit', [
            'prueba' => $prueba,
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'precio_unitario']),
        ]);
    }

    public function update(Request $request, Prueba $prueba): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'especie_id' => ['nullable', 'integer', 'exists:especies,id'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
        ]);

        $prueba->update($validated);

        return redirect()->route('pruebas.index')->with('success', 'Prueba actualizada.');
    }

    public function destroy(Prueba $prueba): RedirectResponse
    {
        $prueba->delete();

        return redirect()->route('pruebas.index')->with('success', 'Prueba eliminada.');
    }
}
