<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = [
        'propietario_id',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio_id',
        'estado_id',
        'codigo_postal',
    ];

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }
}
