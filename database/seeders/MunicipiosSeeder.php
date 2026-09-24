<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipiosSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Inserta el catálogo completo de municipios de México.
     *
     * Los datos viven en database/data/municipios.json, agrupados por la clave
     * del estado (la misma que usa EstadosSeeder). Son los 2,478 municipios y
     * demarcaciones territoriales del catálogo del INEGI.
     *
     * Es idempotente: compara contra (estado_id, nombre) —el índice único de la
     * tabla— e inserta solo lo que falta. La migración que crea la tabla ya
     * sembró Jalisco, así que en una instalación existente este método se
     * encuentra con parte del trabajo hecho.
     *
     * @return int cuántos municipios se agregaron
     */
    public static function sembrar(): int
    {
        $estados = DB::table('estados')->pluck('id', 'clave');

        if ($estados->isEmpty()) {
            return 0;
        }

        $catalogo = json_decode(
            file_get_contents(database_path('data/municipios.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $existentes = DB::table('municipios')
            ->get(['estado_id', 'nombre'])
            ->map(fn ($m) => $m->estado_id.'|'.$m->nombre)
            ->flip();

        $now = now();
        $nuevos = [];

        foreach ($catalogo as $clave => $municipios) {
            $estadoId = $estados[$clave] ?? null;

            if (! $estadoId) {
                continue;
            }

            foreach ($municipios as $nombre) {
                if ($existentes->has($estadoId.'|'.$nombre)) {
                    continue;
                }

                $nuevos[] = [
                    'estado_id' => $estadoId,
                    'nombre' => $nombre,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($nuevos, 500) as $chunk) {
            DB::table('municipios')->insert($chunk);
        }

        return count($nuevos);
    }
}
