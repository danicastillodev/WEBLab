<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Al dar de alta un cliente sólo el nombre es obligatorio; el resto de
     * sus datos puede completarse después.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->change();
            $table->string('telefono', 10)->nullable()->change();
            $table->string('calle')->nullable()->change();
            $table->string('numero_exterior')->nullable()->change();
            $table->string('colonia')->nullable()->change();
            $table->string('codigo_postal', 10)->nullable()->change();
            $table->unsignedBigInteger('estado_id')->nullable()->change();
            $table->unsignedBigInteger('municipio_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable(false)->change();
            $table->string('telefono', 10)->nullable(false)->change();
            $table->string('calle')->nullable(false)->change();
            $table->string('numero_exterior')->nullable(false)->change();
            $table->string('colonia')->nullable(false)->change();
            $table->string('codigo_postal', 10)->nullable(false)->change();
            $table->unsignedBigInteger('estado_id')->nullable(false)->change();
            $table->unsignedBigInteger('municipio_id')->nullable(false)->change();
        });
    }
};
