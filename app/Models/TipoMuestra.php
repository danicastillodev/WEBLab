<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoMuestra extends Model
{
    protected $fillable = ['prueba_id', 'nombre'];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class);
    }

    /**
     * Catálogo general de tipos de muestra (los que no cuelgan de una prueba).
     */
    public function scopeGeneral(Builder $query): void
    {
        $query->whereNull('prueba_id');
    }
}
