<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especie extends Model
{
    protected $fillable = ['nombre'];

    public function razas(): HasMany
    {
        return $this->hasMany(Raza::class);
    }

    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class);
    }
}
