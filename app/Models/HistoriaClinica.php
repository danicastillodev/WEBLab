<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriaClinica extends Model
{
    protected $fillable = [
        'propietario_id',
        'direccion_id',
        'fecha_recepcion',
        'especie_id',
        'raza_id',
        'edad_valor',
        'edad_unidad',
        'cantidad',
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
}
