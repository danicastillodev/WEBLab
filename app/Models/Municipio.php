<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Municipio extends Model
{
    protected $fillable = ['nombre', 'estado_id'];

    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }
}
