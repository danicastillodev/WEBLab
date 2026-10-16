<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos que acompañan al alta de un análisis (Servicio), tomados del
 * modelo original: Categoria, MVZAprobado (aquí "Analista") y Norma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->timestamps();
        });

        Schema::create('analistas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('normas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normas');
        Schema::dropIfExists('analistas');
        Schema::dropIfExists('categorias');
    }
};
