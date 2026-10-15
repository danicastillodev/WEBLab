<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Roles como plantillas de permisos. Editar o eliminar un rol no toca a los
 * usuarios que ya lo tienen: sus permisos se copiaron al asignárselo.
 */
class RolController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => Rol::withCount('users')->orderBy('nombre')->get(['id', 'nombre', 'descripcion']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', $this->opcionesDePermisos());
    }

    public function store(Request $request): RedirectResponse
    {
        Rol::create($this->datos($request));

        return redirect()->route('roles.index')->with('success', 'Rol creado.');
    }

    public function edit(Rol $rol): Response
    {
        return Inertia::render('Roles/Edit', [
            'rol' => [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'descripcion' => $rol->descripcion ?? '',
                ...$rol->plantilla(),
            ],
            ...$this->opcionesDePermisos(),
        ]);
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $rol->update($this->datos($request, $rol));

        return redirect()->route('roles.index')->with('success', 'Rol actualizado.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        // Los usuarios con este rol conservan sus permisos; sólo pierden la referencia.
        $rol->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcionesDePermisos(): array
    {
        return [
            'modulos' => config('modulos.modulos'),
            'secciones' => config('modulos.secciones'),
            'acciones' => config('modulos.acciones'),
            'dias' => config('modulos.dias'),
            'gruposPermisos' => config('permisos_granulares.grupos'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request, ?Rol $rol = null): array
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('roles_usuario')->ignore($rol)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio'],
            'dias_permitidos' => ['nullable', 'array'],
            'dias_permitidos.*' => ['integer', 'between:1,7'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
            'permisos.*.*' => ['boolean'],
            'permisos_granulares' => ['nullable', 'array'],
            'permisos_granulares.*' => ['boolean'],
        ]);

        return [
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            // Sin días marcados = todos los días, igual que en users.
            'dias_permitidos' => ! empty($validated['dias_permitidos'])
                ? collect($validated['dias_permitidos'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all()
                : null,
            'permisos' => Permisos::matrizConcedida($validated['permisos'] ?? []),
            'permisos_granulares' => Permisos::granularesConcedidos($validated['permisos_granulares'] ?? []),
        ];
    }
}
