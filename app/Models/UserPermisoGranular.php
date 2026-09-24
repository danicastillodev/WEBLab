<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permiso puntual (ver config/permisos_granulares.php) concedido a un
 * usuario. A diferencia de UserPermission (matriz módulo × acción), aquí la
 * sola existencia de la fila implica que el permiso está concedido.
 */
class UserPermisoGranular extends Model
{
    protected $table = 'user_permisos_granulares';

    protected $fillable = ['user_id', 'clave'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
