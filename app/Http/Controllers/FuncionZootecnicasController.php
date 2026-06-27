<?php

namespace App\Http\Controllers;

use App\Models\FuncionZootecnica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FuncionZootecnicasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Index', [
            'funciones' => FuncionZootecnica::orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:funcion_zootecnicas'],
        ]);

        FuncionZootecnica::create($validated);

        return redirect()->route('funcion-zootecnicas.index')->with('success', 'Función zootécnica creada.');
    }

    public function edit(FuncionZootecnica $funcionZootecnica): Response
    {
        return Inertia::render('Catalogos/FuncionZootecnicas/Edit', [
            'funcion' => $funcionZootecnica,
        ]);
    }

    public function update(Request $request, FuncionZootecnica $funcionZootecnica): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:funcion_zootecnicas,nombre,' . $funcionZootecnica->id],
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
