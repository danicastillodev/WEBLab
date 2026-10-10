<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Análisis solicitado" y "Tipo de muestra" pasan a capturarse
     * directamente contra los catálogos de Servicios y Productos (en vez de
     * Pruebas/TipoMuestra): así la nota de pago generada desde una historia
     * clínica toma el producto exacto capturado aquí, sin depender de que un
     * admin haya vinculado antes la prueba con su producto de facturación.
     *
     * prueba_id se vuelve opcional: las muestras ya capturadas con el
     * esquema anterior lo conservan, pero las nuevas ya no lo usan.
     */
    public function up(): void
    {
        Schema::table('muestras', function (Blueprint $table) {
            $table->unsignedBigInteger('prueba_id')->nullable()->change();
            $table->foreignId('servicio_id')->nullable()->after('historia_clinica_id')
                ->constrained('servicios')->nullOnDelete();
            $table->foreignId('producto_id')->nullable()->after('servicio_id')
                ->constrained('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('muestras', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropColumn('servicio_id');
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
        });
    }
};
