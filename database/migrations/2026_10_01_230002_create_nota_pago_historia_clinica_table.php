<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una nota de pago puede reunir los conceptos de varias historias
     * clínicas (selección múltiple en "Seleccionar Historias Clínicas").
     */
    public function up(): void
    {
        Schema::create('nota_pago_historia_clinica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_pago_id')->constrained('notas_pago')->cascadeOnDelete();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->cascadeOnDelete();
            $table->timestamps();

            // Nombre explícito: el que genera Laravel por convención mide 66
            // caracteres y MariaDB corta en 64, así que la migración fallaba
            // al crear el índice.
            $table->unique(['nota_pago_id', 'historia_clinica_id'], 'nota_pago_historia_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_pago_historia_clinica');
    }
};
