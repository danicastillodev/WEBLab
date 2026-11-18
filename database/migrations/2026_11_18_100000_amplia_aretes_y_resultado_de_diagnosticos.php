<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los aretes pasan a 15,000 caracteres y el resultado a 16,000 de texto
     * (más las etiquetas del formato), que ya no caben en VARCHAR/TEXT.
     * Ambos tipos sólo ocupan lo que se guarda.
     */
    public function up(): void
    {
        Schema::table('diagnostico_muestras', function (Blueprint $table) {
            $table->text('aretes')->nullable()->change();
        });

        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->mediumText('resultado')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostico_muestras', function (Blueprint $table) {
            $table->string('aretes', 1000)->nullable()->change();
        });

        Schema::table('diagnosticos', function (Blueprint $table) {
            $table->text('resultado')->nullable()->change();
        });
    }
};
