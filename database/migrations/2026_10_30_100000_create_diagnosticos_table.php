<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un diagnóstico por análisis (fila de muestras). Mientras un análisis no
     * tenga diagnóstico se considera pendiente; el diagnóstico guarda su
     * propio estado (en proceso, emitido o cancelado) y las muestras
     * trabajadas con su resultado.
     */
    public function up(): void
    {
        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('muestra_id')->unique()->constrained('muestras')->cascadeOnDelete();
            // Nula sólo si el análisis se cancela sin haberse trabajado.
            $table->date('fecha_realizacion')->nullable();
            $table->text('resultado')->nullable();
            $table->string('estado', 20);
            $table->dateTime('fecha_emision')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('diagnostico_muestras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostico_id')->constrained('diagnosticos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad');
            $table->string('identificacion', 150);
            $table->string('resultado', 20);
            $table->string('aretes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostico_muestras');
        Schema::dropIfExists('diagnosticos');
    }
};
