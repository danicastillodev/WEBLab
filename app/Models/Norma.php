<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Norma extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class);
    }
}
