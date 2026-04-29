<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Propietario extends Model
{
    protected $fillable = ['nombre', 'apellidos', 'curp', 'rfc'];

    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }
}
