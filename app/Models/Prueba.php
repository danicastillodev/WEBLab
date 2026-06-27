<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prueba extends Model
{
    protected $fillable = ['clave', 'nombre', 'especie_id'];

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class);
    }

    public function tiposMuestra(): HasMany
    {
        return $this->hasMany(TipoMuestra::class);
    }
}
