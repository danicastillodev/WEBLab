<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstadosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Estados/Index', [
            'estados' => Estado::orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Estados/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:estados'],
            'clave' => ['required', 'string', 'max:10', 'unique:estados'],
        ]);

        Estado::create($validated);

        return redirect()->route('estados.index')->with('success', 'Estado creado.');
    }

    public function edit(Estado $estado): Response
    {
        return Inertia::render('Catalogos/Estados/Edit', [
            'estado' => $estado,
        ]);
    }

    public function update(Request $request, Estado $estado): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:estados,nombre,'.$estado->id],
            'clave' => ['required', 'string', 'max:10', 'unique:estados,clave,'.$estado->id],
        ]);

        $estado->update($validated);

        return redirect()->route('estados.index')->with('success', 'Estado actualizado.');
    }

    public function destroy(Estado $estado): RedirectResponse
    {
        $estado->delete();

        return redirect()->route('estados.index')->with('success', 'Estado eliminado.');
    }
}
