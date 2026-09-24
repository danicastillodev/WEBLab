<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoMuestra extends Model
{
    protected $fillable = ['prueba_id', 'nombre'];

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }
}
