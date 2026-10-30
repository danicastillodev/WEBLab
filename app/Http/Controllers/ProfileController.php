<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Muestra los datos del usuario, sus permisos y la imagen de perfil.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'esAdmin' => (bool) $user->es_admin,
            'rol' => $user->rol?->nombre,
            'secciones' => $user->es_admin ? [] : $this->permisosPorSeccion($user),
        ]);
    }

    /**
     * Guarda cómo se muestra la imagen de perfil y, si se envía, la foto.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $datos = $request->validate([
            'avatar_tipo' => ['required', Rule::in(User::AVATAR_TIPOS)],
            'foto' => [
                Rule::requiredIf($request->input('avatar_tipo') === 'foto' && ! $user->avatar_path),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
            ],
        ], [
            'foto.required' => 'Selecciona una foto.',
        ]);

        if ($request->hasFile('foto')) {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('foto')->store('avatars', 'local');
        }

        $user->avatar_tipo = $datos['avatar_tipo'];
        $user->save();

        return back()->with('success', 'Imagen de perfil actualizada.');
    }

    /**
     * Sirve la foto de perfil de un usuario (se guarda en el disco privado).
     */
    public function showAvatar(User $user): StreamedResponse
    {
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response($user->avatar_path, null, [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /**
     * Permisos concedidos agrupados igual que en el formulario de usuarios
     * (config/modulos.php 'secciones'), omitiendo las secciones vacías.
     *
     * @return list<array{etiqueta: string, modulos: list<array{etiqueta: string, acciones: list<string>}>, granulares: list<string>}>
     */
    private function permisosPorSeccion(User $user): array
    {
        $modulos = config('modulos.modulos');
        $acciones = config('modulos.acciones');
        $grupos = config('permisos_granulares.grupos');

        $permisos = $user->permissions()->get()->keyBy('modulo');
        $granulares = $user->permisosGranulares()->pluck('clave')->flip();

        return collect(config('modulos.secciones'))
            ->map(fn ($seccion) => [
                'etiqueta' => $seccion['etiqueta'],
                'modulos' => collect($seccion['modulos'])
                    ->map(fn ($modulo) => [
                        'etiqueta' => $modulos[$modulo] ?? $modulo,
                        'acciones' => collect($acciones)
                            ->filter(fn ($_, $accion) => $permisos->get($modulo)?->{$accion})
                            ->values()->all(),
                    ])
                    ->filter(fn ($m) => $m['acciones'])
                    ->values()->all(),
                'granulares' => collect($seccion['grupos_granulares'])
                    ->flatMap(fn ($grupo) => $grupos[$grupo]['permisos'] ?? [])
                    ->filter(fn ($_, $clave) => $granulares->has($clave))
                    ->values()->all(),
            ])
            ->filter(fn ($s) => $s['modulos'] || $s['granulares'])
            ->values()->all();
    }
}
