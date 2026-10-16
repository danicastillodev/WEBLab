<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Rol;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            'users' => User::with(['areas:id,nombre', 'rol:id,nombre'])->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', $this->opcionesDeAcceso());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->reglas());

        // La contraseña no se captura en el alta: queda en blanco y el usuario la
        // crea la primera vez que inicia sesión (ver CrearContrasenaController).
        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'es_admin' => $validated['es_admin'] ?? false,
            'rol_id' => $validated['rol_id'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'dias_permitidos' => $validated['dias_permitidos'] ?? null,
            'password' => null,
        ]);

        $user->areas()->sync($validated['area_ids'] ?? []);

        $this->sincronizarPermisos($user, $request->input('permisos', []));
        $this->sincronizarPermisosGranulares($user, $request->input('permisos_granulares', []));

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado. Se le pedirá crear su contraseña al iniciar sesión.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'tiene_contrasena' => $user->password !== null,
                'area_ids' => $user->areas()->pluck('areas.id')->all(),
                'es_admin' => (bool) $user->es_admin,
                'rol_id' => $user->rol_id,
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
            'rol_id' => $validated['rol_id'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'dias_permitidos' => $validated['dias_permitidos'] ?? null,
        ]);

        $user->save();

        $user->areas()->sync($validated['area_ids'] ?? []);

        $this->sincronizarPermisos($user, $request->input('permisos', []));
        $this->sincronizarPermisosGranulares($user, $request->input('permisos_granulares', []));

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Deja la contraseña en blanco: en su próximo inicio de sesión se le pedirá
     * al usuario crear una nueva. Las sesiones que tenga abiertas se cierran
     * para que no siga entrando con la contraseña anterior.
     */
    public function borrarContrasena(User $user): RedirectResponse
    {
        $user->forceFill(['password' => null, 'remember_token' => null])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return redirect()->route('users.edit', $user)
            ->with('success', 'Contraseña borrada. Se le pedirá crear una nueva al iniciar sesión.');
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
            // Plantillas que el formulario copia sobre la matriz al elegir un rol.
            'roles' => Rol::orderBy('nombre')->get()->map(fn (Rol $rol) => [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                ...$rol->plantilla(),
            ]),
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
    private function reglas(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
            'es_admin' => ['boolean'],
            'rol_id' => ['nullable', 'integer', 'exists:roles_usuario,id'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio'],
            'dias_permitidos' => ['nullable', 'array'],
            'dias_permitidos.*' => ['integer', 'between:1,7'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
            'permisos.*.*' => ['boolean'],
            'permisos_granulares' => ['nullable', 'array'],
            'permisos_granulares.*' => ['boolean'],
        ];
    }

    /**
     * Matriz módulo × acción tal como está guardada, para precargar el formulario.
     *
     * @return array<string, array<string, bool>>
     */
    private function matrizGuardada(User $user): array
    {
        return Permisos::matrizCompleta(
            $user->permissions()->get()->keyBy('modulo')->map->only(array_keys(config('modulos.acciones')))->all()
        );
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
        $user->permissions()->delete();

        foreach (Permisos::matrizConcedida($permisos) as $modulo => $fila) {
            $user->permissions()->create([...$fila, 'modulo' => $modulo]);
        }
    }

    /**
     * Permisos granulares tal como están guardados, para precargar el
     * formulario (clave => bool).
     *
     * @return array<string, bool>
     */
    private function permisosGranularesGuardados(User $user): array
    {
        return Permisos::mapaGranular($user->permisosGranulares()->pluck('clave')->all());
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

        $marcadas = collect(Permisos::granularesConcedidos($permisos))
            ->map(fn (string $clave) => ['clave' => $clave]);

        if ($marcadas->isNotEmpty()) {
            $user->permisosGranulares()->createMany($marcadas->all());
        }
    }
}
