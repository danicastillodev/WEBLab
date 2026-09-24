<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 20)->unique();
            $table->string('siglas', 15)->nullable();
            $table->string('descripcion');
            $table->string('metodo', 100)->nullable();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('referencias')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
