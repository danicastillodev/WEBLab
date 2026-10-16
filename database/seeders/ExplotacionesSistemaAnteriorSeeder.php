<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deja el catálogo de explotaciones igual al del sistema anterior.
 *
 * Los datos viven en database/data/explotaciones.json y salen de
 * `catalogosexplotaciones` en docs/old_db.sql, ya depurados:
 *
 * - "NR", "N/R" y similares quedan vacíos.
 * - Se quitan los puntos finales del nombre, el mismo truco que en
 *   propietarios para capturar "otra" explotación; las que repetían dueño,
 *   nombre y municipio se unificaron, quedándose con el dato más completo.
 * - El único teléfono que venía en caseta pasó a la dirección.
 *
 * El propietario se busca por su id anterior en database/data/propietarios.json,
 * así que PropietariosSistemaAnteriorSeeder debe correr antes.
 */
class ExplotacionesSistemaAnteriorSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Es idempotente: una explotación se reconoce por propietario, nombre y
     * municipio. Las explotaciones que no vengan del sistema anterior se
     * borran, salvo que alguna historia clínica las use.
     *
     * @return array{agregadas: int, borradas: int, conservadas: int, sin_propietario: int}
     */
    public static function sembrar(): array
    {
        $leer = fn (string $archivo) => json_decode(
            file_get_contents(database_path("data/{$archivo}")),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $propietarios = DB::table('propietarios')->get(['id', 'nombre', 'apellidos'])
            ->mapWithKeys(fn ($p) => [$p->nombre.'|'.$p->apellidos => $p->id]);
        $porIdAnterior = [];
        foreach ($leer('propietarios.json') as $p) {
            foreach ($p['ids_anteriores'] as $id) {
                $porIdAnterior[$id] = $propietarios[$p['nombre'].'|'.$p['apellidos']] ?? null;
            }
        }

        $estados = DB::table('estados')->pluck('id', 'clave');
        $municipios = DB::table('municipios')->get(['id', 'estado_id', 'nombre'])
            ->mapWithKeys(fn ($m) => [$m->estado_id.'|'.$m->nombre => $m->id]);

        $anteriores = DB::table('explotaciones')->pluck('id');
        $now = now();
        $mantener = [];
        $agregadas = 0;
        $sinPropietario = 0;

        foreach ($leer('explotaciones.json') as $e) {
            $propietarioId = $porIdAnterior[$e['propietario_anterior']] ?? null;

            if (! $propietarioId) {
                $sinPropietario++;

                continue;
            }

            $estadoId = $estados[$e['estado']] ?? null;
            $municipioId = $e['municipio'] ? ($municipios[$estadoId.'|'.$e['municipio']] ?? null) : null;

            $existente = DB::table('explotaciones')
                ->where('propietario_id', $propietarioId)
                ->where('nombre', $e['nombre'])
                ->where('municipio_id', $municipioId)
                ->value('id');

            if ($existente) {
                $mantener[] = $existente;

                continue;
            }

            $mantener[] = DB::table('explotaciones')->insertGetId([
                'propietario_id' => $propietarioId,
                'nombre' => $e['nombre'],
                'direccion' => $e['direccion'],
                'estado_id' => $estadoId,
                'municipio_id' => $municipioId,
                'caseta' => $e['caseta'],
                'lote' => $e['lote'],
                'parvada' => $e['parvada'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $agregadas++;
        }

        $sobrantes = $anteriores->diff($mantener);
        $enUso = DB::table('historias_clinicas')->whereIn('explotacion_id', $sobrantes)
            ->distinct()->pluck('explotacion_id');
        $borradas = DB::table('explotaciones')->whereIn('id', $sobrantes->diff($enUso))->delete();

        return [
            'agregadas' => $agregadas,
            'borradas' => $borradas,
            'conservadas' => $enUso->count(),
            'sin_propietario' => $sinPropietario,
        ];
    }
}
