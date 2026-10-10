<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folio')->unique();
            $table->foreignId('propietario_id')->constrained('propietarios');
            $table->foreignId('direccion_id')->nullable()->constrained('direcciones')->nullOnDelete();
            $table->string('concepto');
            $table->string('tipo_pago');
            $table->boolean('pagada')->default(false);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva_porcentaje', 5, 2)->default(0);
            $table->decimal('iva_monto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notas_adicionales')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_pago');
    }
};
