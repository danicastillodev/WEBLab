<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained('propietarios')->cascadeOnDelete();
            $table->foreignId('direccion_id')->constrained('direcciones');
            $table->date('fecha_recepcion');
            $table->foreignId('especie_id')->constrained('especies');
            $table->foreignId('raza_id')->constrained('razas');
            $table->unsignedSmallInteger('edad_valor')->nullable();
            $table->enum('edad_unidad', ['Dias', 'Meses', 'Años', 'NR', 'NA']);
            $table->unsignedInteger('cantidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};
