<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatosGeneral extends Model
{
    protected $table = 'datos_generales';

    protected $fillable = [
        'razon_social',
        'nombre_laboratorio',
        'direccion',
        'colonia',
        'estado_id',
        'municipio_id',
        'telefono',
        'email',
        'jefe_laboratorio',
    ];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }
}
