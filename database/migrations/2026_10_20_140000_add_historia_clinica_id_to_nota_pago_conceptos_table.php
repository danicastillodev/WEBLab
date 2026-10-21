<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada concepto sale de una muestra de una historia clínica; guardar de
     * cuál permite imprimir "…, CASO: 6544" en la nota aunque la nota abarque
     * varias historias.
     */
    public function up(): void
    {
        Schema::table('nota_pago_conceptos', function (Blueprint $table) {
            $table->foreignId('historia_clinica_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('historias_clinicas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nota_pago_conceptos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('historia_clinica_id');
        });
    }
};
