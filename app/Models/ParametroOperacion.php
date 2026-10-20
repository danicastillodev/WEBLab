<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroOperacion extends Model
{
    protected $table = 'parametros_operacion';

    protected $fillable = [
        'bd_ruta',
        'bd_nombre',
        'bd_puerto',
        'bd_tiempo_espera',
        'impresora_tickets',
        'folio_automatico_activo',
        'proximo_folio',
        'proximo_numero_caso',
        'ejercicio_numero_caso',
        'iva_porcentaje',
    ];

    protected $casts = [
        'folio_automatico_activo' => 'boolean',
        'proximo_numero_caso' => 'integer',
        'ejercicio_numero_caso' => 'integer',
    ];
}
