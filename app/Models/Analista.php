<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quien efectúa o avala el análisis (MVZAprobado en el modelo original).
 */
class Analista extends Model
{
    protected $fillable = ['nombre', 'area_id'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }
}
