<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Igual que la tabla areas, la columna ya existe en las bases donde se
        // añadió a mano; sólo se crea donde falte.
        if (Schema::hasColumn('users', 'area_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Null = usuario sin área asignada; al borrar el área los usuarios
            // se conservan y simplemente quedan sin asignar.
            $table->foreignId('area_id')->nullable()->after('es_admin')
                ->constrained('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'area_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });
    }
};
