<?php

namespace App\Http\Controllers;

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
            'pruebas' => Prueba::orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Pruebas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clave'  => ['required', 'string', 'max:50', 'unique:pruebas'],
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        Prueba::create($validated);

        return redirect()->route('pruebas.index')->with('success', 'Prueba creada.');
    }

    public function edit(Prueba $prueba): Response
    {
        return Inertia::render('Catalogos/Pruebas/Edit', [
            'prueba' => $prueba,
        ]);
    }

    public function update(Request $request, Prueba $prueba): RedirectResponse
    {
        $validated = $request->validate([
            'clave'  => ['required', 'string', 'max:50', 'unique:pruebas,clave,' . $prueba->id],
            'nombre' => ['required', 'string', 'max:255'],
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
