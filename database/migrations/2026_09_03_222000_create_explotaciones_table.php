<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('explotaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained('propietarios')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->foreignId('estado_id')->nullable()->constrained('estados')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->string('caseta', 50)->nullable();
            $table->string('lote', 50)->nullable();
            $table->string('parvada', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explotaciones');
    }
};
