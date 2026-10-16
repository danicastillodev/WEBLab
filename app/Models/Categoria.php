<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $fillable = ['nombre'];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }
}
