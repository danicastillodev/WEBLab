<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La migración de creación de `especies` insertó especies de mascota
     * (Perro, Gato, etc.) de antes de que el dominio pivotara a zootecnia
     * de ganado. `EspeciesSeeder` nunca las usa, así que quedan huérfanas.
     * Solo se borran si nada les hace referencia, para no tocar datos reales
     * de un entorno donde sí se hayan llegado a usar.
     */
    public function up(): void
    {
        $stale = ['Perro', 'Gato', 'Ave', 'Reptil', 'Roedor', 'Conejo', 'Otro'];

        $ids = DB::table('especies')->whereIn('nombre', $stale)->pluck('id');

        foreach ($ids as $id) {
            $enUso = DB::table('razas')->where('especie_id', $id)->exists()
                || DB::table('funcion_zootecnicas')->where('especie_id', $id)->exists()
                || DB::table('pruebas')->where('especie_id', $id)->exists()
                || DB::table('historias_clinicas')->where('especie_id', $id)->exists();

            if (! $enUso) {
                DB::table('especies')->where('id', $id)->delete();
            }
        }
    }

    /**
     * No reversible: eran datos residuales, no algo que se quiera recuperar.
     */
    public function down(): void
    {
        //
    }
};
