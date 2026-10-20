<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La fecha de recepción guarda también la hora. Las historias existentes
 * quedan a las 00:00 de su día.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dateTime('fecha_recepcion')->change();
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->date('fecha_recepcion')->change();
        });
    }
};
