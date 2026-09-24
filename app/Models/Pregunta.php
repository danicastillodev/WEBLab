<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pregunta extends Model
{
    public const TIPO_TEXTO = 'texto';

    public const TIPO_SI_NO = 'si_no';

    public const TIPO_NUMERO = 'numero';

    public const TIPOS = [self::TIPO_TEXTO, self::TIPO_SI_NO, self::TIPO_NUMERO];

    protected $fillable = ['texto', 'tipo', 'orden', 'activa'];

    protected $casts = ['activa' => 'boolean'];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    /** Preguntas que se muestran en el formulario, en su orden de captura. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('activa', true)->orderBy('orden')->orderBy('id');
    }
}
