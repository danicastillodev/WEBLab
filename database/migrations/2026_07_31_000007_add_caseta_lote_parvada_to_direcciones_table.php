<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            // Datos de granja: sólo aplican a direcciones de explotación
            // pecuaria, por eso son opcionales. Se guardan como texto porque
            // en campo se identifican como "3", "A-12" o "Norte 2".
            $table->string('caseta', 50)->nullable()->after('codigo_postal');
            $table->string('lote', 50)->nullable()->after('caseta');
            $table->string('parvada', 50)->nullable()->after('lote');
        });
    }

    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table) {
            $table->dropColumn(['caseta', 'lote', 'parvada']);
        });
    }
};
