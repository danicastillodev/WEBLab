<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula cada prueba con el producto del catálogo de facturación que le
     * corresponde, para que las notas de pago generadas a partir de una
     * historia clínica tomen el precio exacto en vez de adivinarlo
     * comparando nombres.
     */
    public function up(): void
    {
        Schema::table('pruebas', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('especie_id')
                ->constrained('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pruebas', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn('producto_id');
        });
    }
};
