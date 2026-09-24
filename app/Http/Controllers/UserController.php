<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alta y mantenimiento de usuarios junto con su control de acceso: bandera de
 * administrador, ventana horaria y matriz de permisos módulo × acción que
 * consultan User::puede() y User::dentroDeHorario().
 */
class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('areas:id,nombre')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', $this->opcionesDeAcceso());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->reglas());

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'es_admin' => $validated['es_admin'] ?? false,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'dias_permitidos' => $validated['dias_permitidos'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $user->areas()->sync($validated['area_ids'] ?? []);

        $this->sincronizarPermisos($user, $request->input('permisos', []));
        $this->sincronizarPermisosGranulares($user, $request->input('permisos_granulares', []));

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'area_ids' => $user->areas()->pluck('areas.id')->all(),
                'es_admin' => (bool) $user->es_admin,
                // El input type="time" del formulario espera HH:MM.
                'hora_inicio' => $user->hora_inicio ? substr($user->hora_inicio, 0, 5) : '',
                'hora_fin' => $user->hora_fin ? substr($user->hora_fin, 0, 5) : '',
                'dias_permitidos' => $user->dias_permitidos ?? [],
                'permisos' => $this->matrizGuardada($user),
                'permisos_granulares' => $this->permisosGranularesGuardados($user),
            ],
            ...$this->opcionesDeAcceso(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->reglas($user));

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'es_admin' => $validated['es_admin'] ?? false,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'dias_permitidos' => $validated['dias_permitidos'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->areas()->sync($validated['area_ids'] ?? []);

        $this->sincronizarPermisos($user, $request->input('permisos', []));
        $this->sincronizarPermisosGranulares($user, $request->input('permisos_granulares', []));

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Borrarse a uno mismo dejaría la sesión apuntando a un usuario inexistente.
        if ($request->user()->is($user)) {
            return redirect()->route('users.index')
                ->with('success', 'No puede eliminar su propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    /**
     * Catálogos que alimentan el formulario de acceso (áreas y las columnas de
     * la matriz de permisos, que viven en config/modulos.php).
     *
     * @return array<string, mixed>
     */
    private function opcionesDeAcceso(): array
    {
        return [
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
            'modulos' => config('modulos.modulos'),
            'acciones' => config('modulos.acciones'),
            'dias' => config('modulos.dias'),
            'gruposPermisos' => config('permisos_granulares.grupos'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
            'es_admin' => ['boolean'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio'],
            'dias_permitidos' => ['nullable', 'array'],
            'dias_permitidos.*' => ['integer', 'between:1,7'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
            'permisos.*.*' => ['boolean'],
            'permisos_granulares' => ['nullable', 'array'],
            'permisos_granulares.*' => ['boolean'],
            // En alta la contraseña es obligatoria; al editar, en blanco = sin cambio.
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /**
     * Matriz módulo × acción tal como está guardada, para precargar el formulario.
     *
     * @return array<string, array<string, bool>>
     */
    private function matrizGuardada(User $user): array
    {
        $guardados = $user->permissions()->get()->keyBy('modulo');

        $matriz = [];

        foreach (array_keys(config('modulos.modulos')) as $modulo) {
            foreach (array_keys(config('modulos.acciones')) as $accion) {
                $matriz[$modulo][$accion] = (bool) ($guardados[$modulo]->$accion ?? false);
            }
        }

        return $matriz;
    }

    /**
     * Reescribe los permisos del usuario a partir de la matriz enviada. Sólo se
     * guardan las filas con alguna casilla marcada, y se ignoran módulos o
     * acciones que no estén declarados en config/modulos.php.
     *
     * @param  array<string, array<string, mixed>>  $permisos
     */
    private function sincronizarPermisos(User $user, array $permisos): void
    {
        $acciones = array_keys(config('modulos.acciones'));

        $user->permissions()->delete();

        foreach (array_keys(config('modulos.modulos')) as $modulo) {
            $fila = [];

            foreach ($acciones as $accion) {
                $fila[$accion] = filter_var(
                    $permisos[$modulo][$accion] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
            }

            if (in_array(true, $fila, true)) {
                $user->permissions()->create([...$fila, 'modulo' => $modulo]);
            }
        }
    }

    /**
     * Claves declaradas en config/permisos_granulares.php, sin importar el
     * grupo al que pertenezcan.
     *
     * @return array<int, string>
     */
    private function clavesGranulares(): array
    {
        return collect(config('permisos_granulares.grupos', []))
            ->flatMap(fn (array $grupo) => array_keys($grupo['permisos']))
            ->all();
    }

    /**
     * Permisos granulares tal como están guardados, para precargar el
     * formulario (clave => bool).
     *
     * @return array<string, bool>
     */
    private function permisosGranularesGuardados(User $user): array
    {
        $guardadas = $user->permisosGranulares()->pluck('clave')->all();

        return collect($this->clavesGranulares())
            ->mapWithKeys(fn (string $clave) => [$clave => in_array($clave, $guardadas, true)])
            ->all();
    }

    /**
     * Reescribe los permisos granulares del usuario a partir del mapa
     * enviado. Sólo se guardan las claves marcadas y declaradas en
     * config/permisos_granulares.php.
     *
     * @param  array<string, mixed>  $permisos
     */
    private function sincronizarPermisosGranulares(User $user, array $permisos): void
    {
        $user->permisosGranulares()->delete();

        $marcadas = collect($this->clavesGranulares())
            ->filter(fn (string $clave) => filter_var($permisos[$clave] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->map(fn (string $clave) => ['clave' => $clave]);

        if ($marcadas->isNotEmpty()) {
            $user->permisosGranulares()->createMany($marcadas->all());
        }
    }
}
