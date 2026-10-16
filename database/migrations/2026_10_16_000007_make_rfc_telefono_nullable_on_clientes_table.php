<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los clientes del sistema anterior no siempre tienen RFC ni teléfono: más de
 * la mitad usaba el RFC genérico XAXX010101000, que no cabe en un índice único.
 * La columna los admite vacíos; el formulario los sigue pidiendo, así que se
 * completan al editar el cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable()->change();
            $table->string('telefono', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 13)->nullable(false)->change();
            $table->string('telefono', 10)->nullable(false)->change();
        });
    }
};
