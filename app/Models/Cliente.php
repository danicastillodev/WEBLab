<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A quien se le cobra una nota de pago. No es el propietario de la historia
 * clínica: tiene su propio RFC, teléfono y domicilio, todos obligatorios.
 */
class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'rfc',
        'telefono',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'estado_id',
        'municipio_id',
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function notasPago(): HasMany
    {
        return $this->hasMany(NotaPago::class);
    }
}
