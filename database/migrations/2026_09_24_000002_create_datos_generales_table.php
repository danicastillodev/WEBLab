<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datos_generales', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('nombre_laboratorio');
            $table->string('direccion');
            $table->string('colonia');
            $table->foreignId('estado_id')->constrained('estados');
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->string('telefono', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('jefe_laboratorio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datos_generales');
    }
};
