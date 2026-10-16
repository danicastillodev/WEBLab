<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los tipos de muestra pasan a ser un catálogo general (como en el modelo
 * original) que se relaciona con los análisis. Los registros ligados a una
 * prueba se conservan como histórico; los nuevos se dan de alta sin prueba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_muestras', function (Blueprint $table) {
            $table->unsignedBigInteger('prueba_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tipo_muestras', function (Blueprint $table) {
            $table->unsignedBigInteger('prueba_id')->nullable(false)->change();
        });
    }
};
