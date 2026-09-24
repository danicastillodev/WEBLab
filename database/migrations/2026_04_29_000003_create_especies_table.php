<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especies', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        $now = now();
        DB::table('especies')->insert(array_map(fn ($nombre) => [
            'nombre' => $nombre,
            'created_at' => $now,
            'updated_at' => $now,
        ], ['Perro', 'Gato', 'Ave', 'Reptil', 'Roedor', 'Conejo', 'Otro']));
    }

    public function down(): void
    {
        Schema::dropIfExists('especies');
    }
};
