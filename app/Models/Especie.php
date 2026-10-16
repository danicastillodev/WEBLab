<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especie extends Model
{
    protected $fillable = ['nombre'];

    public function razas(): HasMany
    {
        return $this->hasMany(Raza::class);
    }

    public function pruebas(): HasMany
    {
        return $this->hasMany(Prueba::class);
    }

    public function funcionesZootecnicas(): BelongsToMany
    {
        return $this->belongsToMany(FuncionZootecnica::class, 'especie_funcion_zootecnica');
    }
}
