<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla llegó a crearse a mano en algunas bases (el catálogo de Áreas
        // se desarrolló sin migración), así que sólo se crea donde falte.
        if (Schema::hasTable('areas')) {
            return;
        }

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
