<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RazasSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Deja el catálogo de razas igual al del sistema anterior.
     *
     * Los datos viven en database/data/razas.json, agrupados por el nombre de
     * la especie (el de EspeciesSeeder). Salen de `catalogosrazas` en
     * docs/old_db.sql: en mayúsculas, sin puntos finales y sin los duplicados
     * que solo se distinguían por eso.
     *
     * Las razas que ya existan con el mismo nombre (sin importar mayúsculas ni
     * acentos, como compara MariaDB) se renombran en lugar de duplicarse, así
     * conservan su id. Las demás se borran, salvo que alguna historia clínica
     * las use: esas se quedan para no romper el registro.
     *
     * @return array{agregadas: int, borradas: int, conservadas: int}
     */
    public static function sembrar(): array
    {
        $especies = DB::table('especies')->pluck('id', 'nombre');

        $catalogo = json_decode(
            file_get_contents(database_path('data/razas.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $anteriores = DB::table('razas')->get(['id', 'nombre', 'especie_id'])
            ->keyBy(fn ($r) => $r->especie_id.'|'.self::clave($r->nombre));

        $now = now();
        $mantener = [];
        $nuevas = [];

        foreach ($catalogo as $especie => $razas) {
            $especieId = $especies[$especie] ?? null;

            if (! $especieId) {
                continue;
            }

            foreach ($razas as $nombre) {
                $existente = $anteriores->get($especieId.'|'.self::clave($nombre));

                if ($existente) {
                    if ($existente->nombre !== $nombre) {
                        DB::table('razas')->where('id', $existente->id)
                            ->update(['nombre' => $nombre, 'updated_at' => $now]);
                    }
                    $mantener[] = $existente->id;

                    continue;
                }

                $nuevas[] = [
                    'nombre' => $nombre,
                    'especie_id' => $especieId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($nuevas, 500) as $lote) {
            DB::table('razas')->insert($lote);
        }

        $sobrantes = $anteriores->pluck('id')->diff($mantener);
        $enUso = DB::table('historias_clinicas')->whereIn('raza_id', $sobrantes)
            ->distinct()->pluck('raza_id');
        $borradas = DB::table('razas')->whereIn('id', $sobrantes->diff($enUso))->delete();

        return [
            'agregadas' => count($nuevas),
            'borradas' => $borradas,
            'conservadas' => $enUso->count(),
        ];
    }

    private static function clave(string $nombre): string
    {
        return mb_strtolower(Str::ascii(trim($nombre)));
    }
}
