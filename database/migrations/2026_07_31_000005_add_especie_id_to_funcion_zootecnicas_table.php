<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcion_zootecnicas', function (Blueprint $table) {
            $table->foreignId('especie_id')->nullable()->after('nombre')->constrained('especies')->nullOnDelete();

            // El mismo nombre puede repetirse entre especies (p. ej. "Engorda"),
            // así que la unicidad pasa a ser por especie.
            $table->dropUnique('funcion_zootecnicas_nombre_unique');
            $table->unique(['nombre', 'especie_id']);
        });
    }

    public function down(): void
    {
        Schema::table('funcion_zootecnicas', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'especie_id']);
            $table->dropForeign(['especie_id']);
            $table->dropColumn('especie_id');
            $table->unique('nombre');
        });
    }
};
