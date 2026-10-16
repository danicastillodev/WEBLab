<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deja el catálogo de tipos de muestra y su relación con los análisis igual
 * que en el sistema anterior.
 *
 * Los datos viven en database/data/tipos_muestra.json y salen de
 * `catalogostiposmuestras` y `catalogosserviciostiposmuestras` en
 * docs/old_db.sql; la relación va por la clave del servicio.
 */
class TiposMuestraServiciosSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Los tipos que ya existan con el mismo nombre (sin importar mayúsculas ni
     * acentos) se renombran en lugar de duplicarse, así conservan su id. Los
     * demás se borran, salvo que alguna muestra los use.
     *
     * A cada servicio del sistema anterior se le reemplazan sus tipos de
     * muestra por los de entonces; los servicios que solo existen en el
     * sistema actual conservan los suyos si sobrevivieron.
     *
     * @return array{agregados: int, borrados: int, conservados: int, relaciones: int}
     */
    public static function sembrar(): array
    {
        $catalogo = json_decode(
            file_get_contents(database_path('data/tipos_muestra.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $anteriores = DB::table('tipo_muestras')->whereNull('prueba_id')->get(['id', 'nombre'])
            ->keyBy(fn ($t) => self::clave($t->nombre));
        $todos = DB::table('tipo_muestras')->pluck('id');

        $now = now();
        $ids = [];
        $agregados = 0;

        foreach ($catalogo['tipos'] as $nombre) {
            if ($existente = $anteriores->get(self::clave($nombre))) {
                if ($existente->nombre !== $nombre) {
                    DB::table('tipo_muestras')->where('id', $existente->id)
                        ->update(['nombre' => $nombre, 'updated_at' => $now]);
                }
                $ids[$nombre] = $existente->id;

                continue;
            }

            $ids[$nombre] = DB::table('tipo_muestras')->insertGetId([
                'nombre' => $nombre,
                'prueba_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $agregados++;
        }

        $servicios = DB::table('servicios')->pluck('id', 'clave');
        $relaciones = [];

        foreach ($catalogo['servicios'] as $clave => $tipos) {
            $servicioId = $servicios[ServiciosSistemaAnteriorSeeder::ALIAS_CLAVES[$clave] ?? $clave] ?? null;

            if (! $servicioId) {
                continue;
            }

            DB::table('servicio_tipo_muestra')->where('servicio_id', $servicioId)->delete();

            foreach ($tipos as $nombre) {
                $relaciones[] = ['servicio_id' => $servicioId, 'tipo_muestra_id' => $ids[$nombre]];
            }
        }

        DB::table('servicio_tipo_muestra')->insertOrIgnore($relaciones);

        $sobrantes = $todos->diff(array_values($ids));
        $enUso = DB::table('muestras')->whereIn('tipo_muestra_id', $sobrantes)
            ->distinct()->pluck('tipo_muestra_id');
        $borrados = DB::table('tipo_muestras')->whereIn('id', $sobrantes->diff($enUso))->delete();

        return [
            'agregados' => $agregados,
            'borrados' => $borrados,
            'conservados' => $enUso->count(),
            'relaciones' => count($relaciones),
        ];
    }

    private static function clave(string $nombre): string
    {
        return mb_strtolower(Str::ascii(trim($nombre)));
    }
}
