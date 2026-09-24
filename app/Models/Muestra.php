<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Muestra extends Model
{
    protected $fillable = [
        'historia_clinica_id',
        'prueba_id',
        'cantidad',
        'tipo_muestra_id',
        'notas',
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class);
    }

    public function prueba(): BelongsTo
    {
        return $this->belongsTo(Prueba::class);
    }

    public function tipoMuestra(): BelongsTo
    {
        return $this->belongsTo(TipoMuestra::class);
    }
}
