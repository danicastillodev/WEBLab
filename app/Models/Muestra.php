<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Muestra extends Model
{
    /**
     * Unidades ya facturadas de la muestra en notas de pago vigentes. Una
     * muestra puede repartirse entre varias notas hasta completar su
     * cantidad (como CantidadPorFacturar en el sistema anterior).
     */
    private const SQL_FACTURADO = 'select coalesce(sum(npc.cantidad), 0) from nota_pago_conceptos npc'
        .' inner join notas_pago np on np.id = npc.nota_pago_id'
        .' where npc.muestra_id = muestras.id and np.cancelada = 0';

    /**
     * Muestras a las que todavía les queda cantidad por facturar. Al editar
     * una nota, lo que esa misma nota factura no cuenta como ya facturado.
     */
    public function scopeConPendientePorFacturar(Builder $query, ?int $exceptoNotaPagoId = null): Builder
    {
        return $exceptoNotaPagoId
            ? $query->whereRaw('muestras.cantidad > ('.self::SQL_FACTURADO.' and np.id <> ?)', [$exceptoNotaPagoId])
            : $query->whereRaw('muestras.cantidad > ('.self::SQL_FACTURADO.')');
    }

    protected $fillable = [
        'historia_clinica_id',
        'prueba_id',
        'servicio_id',
        'producto_id',
        'cantidad',
        'tipo_muestra_id',
        'notas',
    ];

    public function historiaClinica(): BelongsTo
    {
        return $this->belongsTo(HistoriaClinica::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
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
