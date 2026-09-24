<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historia_clinica_id')->constrained('historias_clinicas')->cascadeOnDelete();
            // RESTRICT: una pregunta ya contestada no se borra, se desactiva.
            $table->foreignId('pregunta_id')->constrained('preguntas')->restrictOnDelete();
            $table->text('respuesta')->nullable();
            $table->timestamps();

            $table->unique(['historia_clinica_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
