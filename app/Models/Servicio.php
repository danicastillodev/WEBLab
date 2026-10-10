<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $servicio) {
            $servicio->numero ??= static::siguienteNumero();
        });
    }

    protected $fillable = ['clave', 'siglas', 'descripcion', 'metodo_id', 'area_id', 'referencias', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function metodo(): BelongsTo
    {
        return $this->belongsTo(Metodo::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Siguiente número consecutivo global de servicio.
     *
     * Debe llamarse dentro de una transacción: el lockForUpdate evita que dos
     * altas simultáneas tomen el mismo número.
     */
    public static function siguienteNumero(): int
    {
        $ultimo = static::lockForUpdate()->max('numero');

        return ((int) $ultimo) + 1;
    }
}
