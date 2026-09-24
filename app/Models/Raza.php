<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Raza extends Model
{
    protected $fillable = ['nombre', 'especie_id'];

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class);
    }
}
