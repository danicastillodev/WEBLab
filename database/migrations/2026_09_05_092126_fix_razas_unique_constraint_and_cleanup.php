<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // withoutForeignKeyConstraints emite la instrucción propia de cada driver;
        // SET FOREIGN_KEY_CHECKS sólo existe en MySQL y rompía los tests en SQLite.
        Schema::withoutForeignKeyConstraints(function () {
            // Wipe all existing razas (old pet breeds + partial bovino entries)
            DB::table('razas')->truncate();

            // Drop unique index on nombre, add composite unique on (nombre, especie_id)
            Schema::table('razas', function (Blueprint $table) {
                $table->dropUnique(['nombre']);
                $table->unique(['nombre', 'especie_id']);
            });
        });
    }

    public function down(): void
    {
        Schema::table('razas', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'especie_id']);
            $table->unique('nombre');
        });
    }
};
