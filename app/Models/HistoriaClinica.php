<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class HistoriaClinica extends Model
{
    // Eloquent pluralizaría a "historia_clinicas"; la tabla real es "historias_clinicas".
    protected $table = 'historias_clinicas';

    protected static function booted(): void
    {
        static::creating(function (self $historia) {
            $historia->created_by ??= Auth::id();

            // El ejercicio sale de la fecha de recepción y el número de caso es
            // consecutivo dentro de él, así que se asignan juntos y desde aquí:
            // cualquier alta (formulario, seeder, consola) queda numerada igual.
            $historia->ejercicio ??= static::ejercicioDe($historia->fecha_recepcion);
            $historia->numero_caso ??= static::siguienteNumeroCaso($historia->ejercicio);
        });
    }

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_PROCESO = 'en_proceso';

    public const ESTADO_CONCLUIDA = 'concluida';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADO_RESULTADO_PARCIAL = 'resultado_parcial';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROCESO,
        self::ESTADO_CONCLUIDA,
        self::ESTADO_CANCELADA,
        self::ESTADO_RESULTADO_PARCIAL,
    ];

    public const IMPRESO_SI = 'si';

    public const IMPRESO_NO = 'no';

    public const IMPRESO_PARCIAL = 'parcial';

    public const IMPRESOS = [
        self::IMPRESO_SI,
        self::IMPRESO_NO,
        self::IMPRESO_PARCIAL,
    ];

    protected $fillable = [
        'numero_caso',
        'ejercicio',
        'created_by',
        'propietario_id',
        'direccion_id',
        'explotacion_id',
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
        'estado',
        'impreso',
    ];

    /**
     * El ejercicio de una historia es el año de su fecha de recepción:
     * 10/10/2026 → 2026.
     */
    public static function ejercicioDe(string|DateTimeInterface|null $fechaRecepcion): int
    {
        return (int) Carbon::parse($fechaRecepcion)->year;
    }

    /**
     * Siguiente número de caso dentro de un ejercicio. La numeración reinicia
     * cada año (2025 → 1, 2, 3…; 2026 → 1, 2, 3…), así que el consecutivo se
     * calcula sobre el ejercicio y no sobre toda la tabla.
     *
     * Debe llamarse dentro de una transacción: el lockForUpdate evita que dos
     * altas simultáneas del mismo año tomen el mismo número.
     */
    public static function siguienteNumeroCaso(int $ejercicio): int
    {
        $ultimo = static::where('ejercicio', $ejercicio)
            ->lockForUpdate()
            ->max('numero_caso');

        return ((int) $ultimo) + 1;
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    public function explotacion(): BelongsTo
    {
        return $this->belongsTo(Explotacion::class);
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

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }
}
