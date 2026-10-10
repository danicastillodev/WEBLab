<?php

namespace App\Http\Controllers;

use App\Models\Metodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetodosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Metodos/Index', [
            'metodos' => Metodo::orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Metodos/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:metodos'],
        ]);

        Metodo::create($validated);

        return redirect()->route('metodos.index')->with('success', 'Método creado.');
    }

    public function edit(Metodo $metodo): Response
    {
        return Inertia::render('Catalogos/Metodos/Edit', [
            'metodo' => $metodo,
        ]);
    }

    public function update(Request $request, Metodo $metodo): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:metodos,nombre,'.$metodo->id],
        ]);

        $metodo->update($validated);

        return redirect()->route('metodos.index')->with('success', 'Método actualizado.');
    }

    public function destroy(Metodo $metodo): RedirectResponse
    {
        $metodo->delete();

        return redirect()->route('metodos.index')->with('success', 'Método eliminado.');
    }
}
