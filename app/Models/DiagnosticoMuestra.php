<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticoMuestra extends Model
{
    protected $fillable = ['diagnostico_id', 'cantidad', 'identificacion', 'resultado', 'aretes'];

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }
}
