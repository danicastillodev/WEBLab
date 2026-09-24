<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AreasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Areas/Index', [
            // Cada área viaja con sus miembros; 'usuarios' es el catálogo
            // completo del que se eligen los que se van a agregar.
            'areas' => Area::with('users:id,name')
                ->withCount('users')
                ->orderBy('nombre')
                ->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Areas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:areas'],
        ]);

        Area::create(['nombre' => $request->nombre]);

        return redirect()->route('areas.index')->with('success', 'Área creada.');
    }

    public function edit(Area $area): Response
    {
        return Inertia::render('Catalogos/Areas/Edit', [
            'area' => $area,
        ]);
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:areas,nombre,'.$area->id],
        ]);

        $area->update(['nombre' => $request->nombre]);

        return redirect()->route('areas.index')->with('success', 'Área actualizada.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $area->delete();

        return redirect()->route('areas.index')->with('success', 'Área eliminada.');
    }

    /**
     * Agrega un usuario del catálogo como miembro del área. Un usuario puede
     * pertenecer a varias áreas, y agregarlo dos veces no duplica la fila.
     */
    public function agregarUsuario(Request $request, Area $area): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $area->users()->syncWithoutDetaching([$validated['user_id']]);

        return redirect()->route('areas.index')->with('success', 'Usuario agregado al área.');
    }

    public function quitarUsuario(Area $area, User $user): RedirectResponse
    {
        $area->users()->detach($user->id);

        return redirect()->route('areas.index')->with('success', 'Usuario quitado del área.');
    }
}
