<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_operacion', function (Blueprint $table) {
            $table->id();
            $table->string('bd_ruta')->default('192.168.1.50');
            $table->string('bd_nombre')->default('NETLAB');
            $table->unsignedInteger('bd_puerto')->default(1433);
            $table->unsignedInteger('bd_tiempo_espera')->default(15);
            $table->string('impresora_tickets')->nullable()->default('Ticket');
            $table->boolean('folio_automatico_activo')->default(true);
            $table->unsignedBigInteger('proximo_folio')->default(55351);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_operacion');
    }
};
