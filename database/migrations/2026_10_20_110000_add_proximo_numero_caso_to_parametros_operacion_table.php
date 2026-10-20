<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El número de caso es consecutivo dentro de un ejercicio, así que el
     * próximo número configurable se guarda junto al ejercicio al que aplica:
     * al cambiar de año el valor deja de tener efecto y la numeración reinicia.
     */
    public function up(): void
    {
        Schema::table('parametros_operacion', function (Blueprint $table) {
            $table->unsignedBigInteger('proximo_numero_caso')->nullable()->after('proximo_folio');
            $table->unsignedSmallInteger('ejercicio_numero_caso')->nullable()->after('proximo_numero_caso');
        });
    }

    public function down(): void
    {
        Schema::table('parametros_operacion', function (Blueprint $table) {
            $table->dropColumn(['proximo_numero_caso', 'ejercicio_numero_caso']);
        });
    }
};
