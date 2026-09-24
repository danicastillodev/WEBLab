<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un usuario pasa de tener un área a tener varias: la relación se mueve de
     * users.area_id a la tabla pivote area_user, conservando las asignaciones
     * que ya existían.
     */
    public function up(): void
    {
        if (! Schema::hasTable('area_user')) {
            Schema::create('area_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['area_id', 'user_id']);
            });
        }

        if (! Schema::hasColumn('users', 'area_id')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('area_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('area_user')->insertOrIgnore([
                    'area_id' => $user->area_id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('users', function (Blueprint $table) {
            // La clave foránea se suelta antes que la columna: SQLite no puede
            // borrar una columna que siga referenciada.
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'area_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('area_id')->nullable()->after('es_admin')
                    ->constrained('areas')->nullOnDelete();
            });

            // Al volver a una sola área se conserva la primera asignada.
            DB::table('area_user')->orderBy('user_id')->orderBy('id')->each(function ($fila) {
                DB::table('users')
                    ->where('id', $fila->user_id)
                    ->whereNull('area_id')
                    ->update(['area_id' => $fila->area_id]);
            });
        }

        Schema::dropIfExists('area_user');
    }
};
