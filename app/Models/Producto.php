<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $fillable = ['nombre', 'servicio_id', 'precio_unitario', 'activo'];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}
