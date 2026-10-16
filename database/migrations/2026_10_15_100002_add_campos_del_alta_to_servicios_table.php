<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('nombre_analisis')->nullable()->after('descripcion');
            $table->foreignId('categoria_id')->nullable()->after('metodo_id')
                ->constrained('categorias')->nullOnDelete();
            $table->foreignId('analista_id')->nullable()->after('area_id')
                ->constrained('analistas')->nullOnDelete();
            $table->boolean('compuesto')->default(false)->after('referencias');
            $table->boolean('independiente')->default(false)->after('compuesto');
        });

        Schema::create('norma_servicio', function (Blueprint $table) {
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('norma_id')->constrained('normas')->cascadeOnDelete();
            $table->primary(['servicio_id', 'norma_id']);
        });

        Schema::create('servicio_tipo_muestra', function (Blueprint $table) {
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('tipo_muestra_id')->constrained('tipo_muestras')->cascadeOnDelete();
            $table->primary(['servicio_id', 'tipo_muestra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_tipo_muestra');
        Schema::dropIfExists('norma_servicio');

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
            $table->dropConstrainedForeignId('analista_id');
            $table->dropColumn(['nombre_analisis', 'compuesto', 'independiente']);
        });
    }
};
