<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Explotacion extends Model
{
    protected $table = 'explotaciones';

    protected $fillable = [
        'propietario_id',
        'nombre',
        'direccion',
        'estado_id',
        'municipio_id',
        'caseta',
        'lote',
        'parvada',
    ];

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }
}
