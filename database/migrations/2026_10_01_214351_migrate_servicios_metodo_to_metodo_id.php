<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->foreignId('metodo_id')->nullable()->after('metodo')->constrained('metodos')->nullOnDelete();
        });

        // El método era texto libre; cada valor distinto ya usado se vuelve una
        // fila del catálogo y los servicios existentes quedan enlazados a ella.
        $valores = DB::table('servicios')->whereNotNull('metodo')->distinct()->pluck('metodo');

        foreach ($valores as $nombre) {
            $metodoId = DB::table('metodos')->where('nombre', $nombre)->value('id');

            if (! $metodoId) {
                $metodoId = DB::table('metodos')->insertGetId([
                    'nombre' => $nombre,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('servicios')->where('metodo', $nombre)->update(['metodo_id' => $metodoId]);
        }

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('metodo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('metodo', 100)->nullable()->after('descripcion');
        });

        DB::table('servicios')->join('metodos', 'metodos.id', '=', 'servicios.metodo_id')
            ->update(['servicios.metodo' => DB::raw('metodos.nombre')]);

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metodo_id');
        });
    }
};
