<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muestras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->cascadeOnDelete();
            $table->foreignId('prueba_id')->constrained('pruebas');
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('tipo_muestra')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muestras');
    }
};
