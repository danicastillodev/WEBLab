<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => Role::orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:roles'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Role::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name'  => 'web',
        ]);

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Roles/Edit', [
            'role' => $role,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }
}
