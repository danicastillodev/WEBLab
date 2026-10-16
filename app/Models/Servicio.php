<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $servicio) {
            $servicio->numero ??= static::siguienteNumero();
        });

        // Un análisis desactivado arrastra a sus productos; al reactivarlo
        // los productos se quedan como estén y se reactivan uno por uno.
        static::updated(function (self $servicio) {
            if ($servicio->wasChanged('activo') && ! $servicio->activo) {
                $servicio->productos()->where('activo', true)->update(['activo' => false]);
            }
        });
    }

    protected $fillable = [
        'clave', 'siglas', 'descripcion', 'nombre_analisis', 'metodo_id', 'categoria_id',
        'area_id', 'analista_id', 'referencias', 'compuesto', 'independiente', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'compuesto' => 'boolean',
        'independiente' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function metodo(): BelongsTo
    {
        return $this->belongsTo(Metodo::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(Analista::class);
    }

    public function normas(): BelongsToMany
    {
        return $this->belongsToMany(Norma::class);
    }

    public function tiposMuestra(): BelongsToMany
    {
        return $this->belongsToMany(TipoMuestra::class);
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
