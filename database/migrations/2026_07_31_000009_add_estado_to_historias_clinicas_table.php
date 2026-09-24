<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            // El estado lo mueve el sistema conforme avanza el proceso; no se
            // captura en el formulario. Toda historia nueva nace "pendiente".
            $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])
                ->default('pendiente')
                ->after('notas_adicionales');

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
