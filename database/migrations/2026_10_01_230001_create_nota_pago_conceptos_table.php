<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_pago_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_pago_id')->constrained('notas_pago')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('importe', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_pago_conceptos');
    }
};
