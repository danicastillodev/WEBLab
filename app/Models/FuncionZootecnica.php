<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FuncionZootecnica extends Model
{
    protected $fillable = ['nombre'];

    public function especies(): BelongsToMany
    {
        return $this->belongsToMany(Especie::class, 'especie_funcion_zootecnica');
    }
}
