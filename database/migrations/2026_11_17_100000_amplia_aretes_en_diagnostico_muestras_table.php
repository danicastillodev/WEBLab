<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los aretes de una fila pueden ser muchos (uno por muestra), así que el
     * campo pasa de 255 a 1000 caracteres.
     */
    public function up(): void
    {
        Schema::table('diagnostico_muestras', function (Blueprint $table) {
            $table->string('aretes', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostico_muestras', function (Blueprint $table) {
            $table->string('aretes')->nullable()->change();
        });
    }
};
