<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Veces que se ha generado la hoja de resultados de un diagnóstico emitido
 * ("Num. impresión" en el reporte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->unsignedInteger('impresiones')->default(0)->after('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->dropColumn('impresiones');
        });
    }
};
