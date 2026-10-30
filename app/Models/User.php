<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'email', 'password', 'username', 'es_admin', 'rol_id', 'hora_inicio', 'hora_fin', 'dias_permitidos'])]
#[Hidden(['password', 'remember_token', 'avatar_path'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Formas de mostrar la imagen de perfil. */
    public const AVATAR_TIPOS = ['inicial', 'iniciales', 'foto'];

    protected $attributes = ['avatar_tipo' => 'inicial'];

    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'es_admin' => 'boolean',
            'dias_permitidos' => 'array',
        ];
    }

    /** URL de la foto de perfil, o null si el usuario no ha subido una. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path
            ? route('profile.avatar.show', ['user' => $this->id, 'v' => md5($this->avatar_path)])
            : null);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)->withTimestamps();
    }

    /** Rol del que se tomaron los permisos como plantilla (informativo). */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function permisosGranulares(): HasMany
    {
        return $this->hasMany(UserPermisoGranular::class);
    }

    /**
     * Comprueba si el usuario tiene permiso para realizar una acción en un módulo.
     * Los administradores siempre tienen acceso.
     */
    public function puede(string $modulo, string $accion): bool
    {
        if ($this->es_admin) {
            return true;
        }

        return $this->permissions()
            ->where('modulo', $modulo)
            ->where($accion, true)
            ->exists();
    }

    /**
     * Comprueba si el usuario tiene concedido un permiso granular puntual
     * (ver config/permisos_granulares.php). Los administradores siempre
     * tienen acceso.
     */
    public function tienePermisoGranular(string $clave): bool
    {
        if ($this->es_admin) {
            return true;
        }

        return $this->permisosGranulares()->where('clave', $clave)->exists();
    }

    /**
     * Comprueba si la petición actual cae dentro de la ventana de acceso
     * configurada para el usuario. Los administradores siempre tienen acceso.
     */
    public function dentroDeHorario(): bool
    {
        if ($this->es_admin) {
            return true;
        }

        $ahora = Carbon::now();

        // Días permitidos (1 = lunes … 7 = domingo, según Carbon::dayOfWeekIso)
        $dias = $this->dias_permitidos;
        if (! empty($dias) && ! in_array($ahora->dayOfWeekIso, $dias)) {
            return false;
        }

        // Ventana horaria
        if ($this->hora_inicio && $this->hora_fin) {
            $inicio = Carbon::createFromTimeString($this->hora_inicio);
            $fin = Carbon::createFromTimeString($this->hora_fin);
            $actual = Carbon::createFromTimeString($ahora->format('H:i'));

            // Ventana normal (ej. 08:00 – 18:00)
            if ($inicio <= $fin) {
                return $actual->between($inicio, $fin);
            }

            // Ventana que cruza medianoche (ej. 22:00 – 06:00)
            return $actual->greaterThanOrEqualTo($inicio)
                || $actual->lessThanOrEqualTo($fin);
        }

        return true;
    }

    /**
     * Matriz de permisos por módulo usada por el sidebar.
     * Los administradores tienen acceso total; los demás no tienen acceso
     * a módulos restringidos hasta que se implemente un sistema de permisos.
     *
     * @return array<string, array{ver: bool, crear: bool}>
     */
    public function matrizDePermisos(): array
    {
        if ($this->es_admin) {
            $modulos = array_keys(config('modulos.modulos', []));

            return collect($modulos)
                ->mapWithKeys(fn ($m) => [$m => ['ver' => true, 'crear' => true]])
                ->all();
        }

        return $this->permissions()
            ->get(['modulo', 'ver', 'crear'])
            ->keyBy('modulo')
            ->map(fn ($p) => ['ver' => $p->ver, 'crear' => $p->crear])
            ->all();
    }

    /**
     * Mapa clave => true de los permisos granulares concedidos (ver
     * config/permisos_granulares.php), usado por el sidebar para mostrar
     * enlaces que no encajan en la matriz módulo × acción. Los
     * administradores tienen todos los permisos concedidos.
     *
     * @return array<string, bool>
     */
    public function matrizDePermisosGranulares(): array
    {
        if ($this->es_admin) {
            $claves = collect(config('permisos_granulares.grupos'))
                ->flatMap(fn ($grupo) => array_keys($grupo['permisos']));

            return $claves->mapWithKeys(fn ($clave) => [$clave => true])->all();
        }

        return $this->permisosGranulares()
            ->pluck('clave')
            ->mapWithKeys(fn ($clave) => [$clave => true])
            ->all();
    }
}
