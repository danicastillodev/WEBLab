<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaPagoConcepto extends Model
{
    protected $fillable = [
        'nota_pago_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'importe',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'importe' => 'decimal:2',
    ];

    public function notaPago(): BelongsTo
    {
        return $this->belongsTo(NotaPago::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
