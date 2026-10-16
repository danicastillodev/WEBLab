<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class NotaPago extends Model
{
    // Eloquent pluralizaría a "nota_pagos"; la tabla real es "notas_pago".
    protected $table = 'notas_pago';

    public const CONCEPTOS = ['Laboratorios', 'Consulta', 'Otros'];

    public const TIPO_PAGO_CONTADO = 'Contado';

    public const TIPO_PAGO_CREDITO = 'Crédito';

    public const TIPOS_PAGO = [self::TIPO_PAGO_CONTADO, self::TIPO_PAGO_CREDITO];

    protected static function booted(): void
    {
        static::creating(function (self $notaPago) {
            $notaPago->created_by ??= Auth::id();
        });
    }

    protected $fillable = [
        'folio',
        'cliente_id',
        'propietario_id',
        'direccion_id',
        'concepto',
        'tipo_pago',
        'pagada',
        'subtotal',
        'iva_porcentaje',
        'iva_monto',
        'total',
        'notas_adicionales',
        'created_by',
    ];

    protected $casts = [
        'pagada' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_porcentaje' => 'decimal:2',
        'iva_monto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Siguiente folio a partir de los parámetros de operación. Debe llamarse
     * dentro de una transacción: el lockForUpdate evita que dos notas
     * simultáneas tomen el mismo folio.
     */
    public static function siguienteFolio(): int
    {
        $parametros = ParametroOperacion::query()->lockForUpdate()->first();
        $folio = $parametros?->proximo_folio ?? 1;

        if ($parametros) {
            $parametros->update(['proximo_folio' => $folio + 1]);
        }

        return $folio;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    public function historiasClinicas(): BelongsToMany
    {
        return $this->belongsToMany(HistoriaClinica::class, 'nota_pago_historia_clinica');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(NotaPagoConcepto::class);
    }
}
