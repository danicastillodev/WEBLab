<?php

namespace App\Models;

use App\Support\Permisos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plantilla de permisos (Recepción, Análisis, Auxiliar, Dirección...). Al
 * asignarla a un usuario sus permisos se copian a él y pueden ajustarse
 * después; cambiar el rol no altera a los usuarios que ya lo tienen.
 *
 * La tabla es roles_usuario y no roles porque el servidor puede conservar la
 * tabla roles que dejó la antigua instalación de Spatie.
 */
class Rol extends Model
{
    protected $table = 'roles_usuario';

    protected $fillable = ['nombre', 'descripcion', 'permisos', 'permisos_granulares'];

    protected function casts(): array
    {
        return [
            'permisos' => 'array',
            'permisos_granulares' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permisos en el formato del formulario: matriz completa módulo × acción
     * y mapa clave => bool de permisos granulares.
     *
     * @return array{permisos: array<string, array<string, bool>>, permisos_granulares: array<string, bool>}
     */
    public function plantilla(): array
    {
        return [
            'permisos' => Permisos::matrizCompleta($this->permisos ?? []),
            'permisos_granulares' => Permisos::mapaGranular($this->permisos_granulares ?? []),
        ];
    }
}
