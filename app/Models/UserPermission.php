<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $fillable = ['user_id', 'modulo', 'ver', 'crear', 'editar', 'eliminar'];

    protected function casts(): array
    {
        return [
            'ver' => 'boolean',
            'crear' => 'boolean',
            'editar' => 'boolean',
            'eliminar' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
