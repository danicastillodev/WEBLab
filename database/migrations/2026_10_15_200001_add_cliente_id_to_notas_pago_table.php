<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las notas nuevas se emiten a un cliente; propietario y dirección se
     * vuelven opcionales y sólo se conservan para las notas ya emitidas.
     */
    public function up(): void
    {
        Schema::table('notas_pago', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('folio')
                ->constrained('clientes');
            $table->unsignedBigInteger('propietario_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notas_pago', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
