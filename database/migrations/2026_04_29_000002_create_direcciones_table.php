<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained('propietarios')->cascadeOnDelete();
            $table->string('calle');
            $table->string('numero_exterior');
            $table->string('numero_interior')->nullable();
            $table->string('colonia');
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->foreignId('estado_id')->constrained('estados');
            $table->string('codigo_postal', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
