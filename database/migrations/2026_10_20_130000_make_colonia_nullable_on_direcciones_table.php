<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La colonia de la dirección del propietario pasa a ser opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->string('colonia')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->string('colonia')->nullable(false)->change();
        });
    }
};
