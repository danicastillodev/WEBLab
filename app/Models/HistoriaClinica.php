<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoriaClinica extends Model
{
    protected $fillable = [
        'propietario_id',
        'direccion_id',
        'fecha_recepcion',
        'fecha_muestra',
        'especie_id',
        'raza_id',
        'funcion_zootecnica_id',
        'sexo',
        'edad_valor',
        'edad_unidad',
        'cantidad',
        'animales_explotacion',
        'animales_muertos',
        'animales_enfermos',
        'notas_adicionales',
    ];

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class);
    }

    public function raza(): BelongsTo
    {
        return $this->belongsTo(Raza::class);
    }

    public function funcionZootecnica(): BelongsTo
    {
        return $this->belongsTo(FuncionZootecnica::class);
    }

    public function muestras(): HasMany
    {
        return $this->hasMany(Muestra::class);
    }
}
