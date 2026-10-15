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

    protected $fillable = [
        'nombre', 'descripcion', 'hora_inicio', 'hora_fin', 'dias_permitidos',
        'permisos', 'permisos_granulares',
    ];

    protected function casts(): array
    {
        return [
            'dias_permitidos' => 'array',
            'permisos' => 'array',
            'permisos_granulares' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Horario y permisos en el formato del formulario: horas HH:MM (o ''),
     * días 1–7, matriz completa módulo × acción y mapa clave => bool de
     * permisos granulares.
     *
     * @return array{hora_inicio: string, hora_fin: string, dias_permitidos: array<int, int>, permisos: array<string, array<string, bool>>, permisos_granulares: array<string, bool>}
     */
    public function plantilla(): array
    {
        return [
            // El input type="time" del formulario espera HH:MM.
            'hora_inicio' => $this->hora_inicio ? substr($this->hora_inicio, 0, 5) : '',
            'hora_fin' => $this->hora_fin ? substr($this->hora_fin, 0, 5) : '',
            'dias_permitidos' => $this->dias_permitidos ?? [],
            'permisos' => Permisos::matrizCompleta($this->permisos ?? []),
            'permisos_granulares' => Permisos::mapaGranular($this->permisos_granulares ?? []),
        ];
    }
}
