<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostico extends Model
{
    /**
     * Estado de un análisis que todavía no tiene diagnóstico. No se guarda:
     * es la ausencia de fila en esta tabla.
     */
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_PROCESO = 'en_proceso';

    public const ESTADO_EMITIDO = 'emitido';

    public const ESTADO_CANCELADO = 'cancelado';

    /** Estados por los que se puede filtrar el listado, incluido el pendiente. */
    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_EMITIDO,
        self::ESTADO_CANCELADO,
    ];

    public const RESULTADOS = ['negativo', 'positivo', 'sospechoso'];

    /** Largo máximo del resultado, contado sobre el texto sin formato. */
    public const MAXIMO_RESULTADO = 520;

    protected $fillable = [
        'muestra_id',
        'fecha_realizacion',
        'resultado',
        'estado',
        'fecha_emision',
        'created_by',
    ];

    public function muestra(): BelongsTo
    {
        return $this->belongsTo(Muestra::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Muestras trabajadas, cada una con su resultado. */
    public function muestras(): HasMany
    {
        return $this->hasMany(DiagnosticoMuestra::class);
    }
}
