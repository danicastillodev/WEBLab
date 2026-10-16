<?php

use Database\Seeders\FuncionesZootecnicasSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Las funciones de relleno que había antes, por nombre, a su equivalente
     * del sistema anterior. Las que no tienen equivalente (Trabajo, Fibra,
     * Lana, Carga) dejan la historia sin función.
     */
    private const EQUIVALENCIAS = [
        'ORNAMENTAL' => 'ORNATO',
    ];

    /**
     * Una función zootécnica deja de pertenecer a una sola especie: "ENGORDA"
     * o "PIE DE CRIA" aplican a varias. Pasa a una pivote especie ↔ función,
     * y el catálogo de relleno se reemplaza por el del sistema anterior
     * (FuncionesZootecnicasSeeder::CATALOGO, con sus ids originales).
     */
    public function up(): void
    {
        Schema::create('especie_funcion_zootecnica', function (Blueprint $table) {
            $table->foreignId('especie_id')->constrained('especies')->cascadeOnDelete();
            $table->foreignId('funcion_zootecnica_id')->constrained('funcion_zootecnicas')->cascadeOnDelete();
            $table->primary(['especie_id', 'funcion_zootecnica_id']);
        });

        // Qué función del sistema anterior le toca a cada historia, antes de
        // borrar las de relleno (sus ids se enciman con los nuevos).
        $nuevoId = collect(FuncionesZootecnicasSeeder::CATALOGO)
            ->mapWithKeys(fn ($nombre, $id) => [$this->clave($nombre) => $id]);
        $equivalente = DB::table('funcion_zootecnicas')->pluck('nombre', 'id')
            ->map(function ($nombre) use ($nuevoId) {
                $clave = $this->clave($nombre);

                return $nuevoId[self::EQUIVALENCIAS[$clave] ?? $clave] ?? null;
            });
        $historias = DB::table('historias_clinicas')->whereNotNull('funcion_zootecnica_id')
            ->pluck('funcion_zootecnica_id', 'id');

        DB::table('historias_clinicas')->update(['funcion_zootecnica_id' => null]);
        DB::table('funcion_zootecnicas')->delete();

        Schema::table('funcion_zootecnicas', function (Blueprint $table) {
            $table->dropUnique(['nombre', 'especie_id']);
            $table->dropForeign(['especie_id']);
        });
        Schema::table('funcion_zootecnicas', function (Blueprint $table) {
            $table->dropColumn('especie_id');
            $table->unique('nombre');
        });

        FuncionesZootecnicasSeeder::sembrar();

        foreach ($historias->groupBy(fn ($funcion) => $equivalente[$funcion] ?? null, preserveKeys: true) as $funcion => $grupo) {
            if ($funcion) {
                DB::table('historias_clinicas')->whereIn('id', $grupo->keys())->update(['funcion_zootecnica_id' => $funcion]);
            }
        }
    }

    public function down(): void
    {
        // Sin reversa a propósito: las funciones de relleno y su especie única
        // no se pueden reconstruir a partir de la pivote.
    }

    private function clave(string $nombre): string
    {
        return Str::upper(Str::ascii($nombre));
    }
};
