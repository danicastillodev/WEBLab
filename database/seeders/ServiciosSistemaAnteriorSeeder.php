<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Completa los catálogos del alta de análisis con los del sistema anterior:
 * métodos, normas, MVZ aprobados (analistas), categorías, áreas y servicios.
 *
 * Los datos viven en database/data/catalogos_servicio.json y salen de
 * docs/old_db.sql (`catalogosmetodos`, `catalogosnormas`,
 * `catalogosmvzaprobados`, `catalogoscategorias`, `catalogosareas`,
 * `catalogosservicios` y `catalogosserviciosnormas`).
 *
 * Lo que ya existe manda: los nombres se comparan sin mayúsculas ni acentos
 * (como compara MariaDB) y un servicio que ya está dado de alta solo recibe los
 * campos que tenga vacíos.
 */
class ServiciosSistemaAnteriorSeeder extends Seeder
{
    /** Métodos que en el catálogo actual se capturaron con otro nombre. */
    private const ALIAS_METODOS = [
        'INMUNODIFUSION DOBLE EN GEL' => 'INMUNODIFUSION DOBLE',
        'INMUNODIFUSION RADIAL CON HAPTENO NATIVO' => 'INMUNODIFUSION RADIAL',
        'INMUNOFLUORESCENCIA DIRECTA' => 'INMUNOFLUORESCENCIA',
    ];

    /** Áreas con el nombre que usa AreasSeeder (en una base nueva aún no existen). */
    private const ALIAS_AREAS = [
        'Patologia' => 'Patología',
        'Bacteriologia' => 'Bacteriología',
        'Virologia' => 'Virología',
        'Serologia' => 'Serología',
        'Toxicologia' => 'Toxicología',
        'Recepcion' => 'Recepción',
        'PCR TB' => 'PCR',
    ];

    /** Servicios que cambiaron de clave (mismas siglas y descripción). */
    public const ALIAS_CLAVES = ['SEE04' => 'SEP04', 'SEE61' => 'SEP61', 'CLH2O' => 'CLH20'];

    /** @var array<string, array<string, int>> ids por tabla y nombre normalizado */
    private static array $ids = [];

    private static int $agregados = 0;

    public function run(): void
    {
        self::sembrar();
    }

    /**
     * @return array<string, int> cuántos registros se agregaron o completaron
     */
    public static function sembrar(): array
    {
        self::$ids = [];

        $catalogo = json_decode(
            file_get_contents(database_path('data/catalogos_servicio.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $conteo = [];

        self::$agregados = 0;
        foreach ($catalogo['metodos'] as $nombre) {
            self::asegurar('metodos', self::ALIAS_METODOS[$nombre] ?? $nombre);
        }
        $conteo['metodos'] = self::$agregados;

        self::$agregados = 0;
        foreach ($catalogo['normas'] as $norma) {
            $id = self::asegurar('normas', $norma['nombre'], ['descripcion' => $norma['descripcion']]);
            DB::table('normas')->where('id', $id)->whereNull('descripcion')
                ->update(['descripcion' => $norma['descripcion']]);
        }
        $conteo['normas'] = self::$agregados;

        self::$agregados = 0;
        foreach ($catalogo['analistas'] as $nombre) {
            self::asegurar('analistas', $nombre);
        }
        $conteo['analistas'] = self::$agregados;

        $conteo += self::sembrarServicios($catalogo['servicios']);

        return $conteo;
    }

    /**
     * @param  list<array<string, mixed>>  $servicios
     * @return array<string, int>
     */
    private static function sembrarServicios(array $servicios): array
    {
        $existentes = DB::table('servicios')->get()->keyBy('clave');
        $numero = (int) DB::table('servicios')->max('numero');
        $now = now();
        $agregados = 0;
        $completados = 0;
        $normas = [];

        foreach ($servicios as $s) {
            $clave = self::ALIAS_CLAVES[$s['clave']] ?? $s['clave'];

            $datos = [
                'siglas' => $s['siglas'],
                'nombre_analisis' => $s['nombre_analisis'],
                'metodo_id' => self::asegurar('metodos', self::ALIAS_METODOS[$s['metodo']] ?? $s['metodo']),
                'categoria_id' => self::asegurar('categorias', $s['categoria']),
                'area_id' => self::asegurar('areas', self::ALIAS_AREAS[$s['area']] ?? $s['area']),
                'analista_id' => self::asegurar('analistas', $s['analista']),
                'referencias' => $s['referencias'],
            ];

            if ($actual = $existentes->get($clave)) {
                $vacios = array_filter(
                    $datos,
                    fn ($valor, $campo) => $valor !== null && $actual->{$campo} === null,
                    ARRAY_FILTER_USE_BOTH
                );

                // El catálogo actual nunca capturó estas dos banderas.
                if (! $actual->compuesto && ! $actual->independiente) {
                    $vacios += ['compuesto' => $s['compuesto'], 'independiente' => $s['independiente']];
                }

                if ($vacios) {
                    DB::table('servicios')->where('id', $actual->id)
                        ->update($vacios + ['updated_at' => $now]);
                    $completados++;
                }
                $servicioId = $actual->id;
            } else {
                $servicioId = DB::table('servicios')->insertGetId($datos + [
                    'numero' => ++$numero,
                    'clave' => $clave,
                    'descripcion' => $s['descripcion'],
                    'compuesto' => $s['compuesto'],
                    'independiente' => $s['independiente'],
                    'activo' => $s['activo'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $agregados++;
            }

            foreach ($s['normas'] as $norma) {
                $normas[] = ['servicio_id' => $servicioId, 'norma_id' => self::asegurar('normas', $norma)];
            }
        }

        return [
            'servicios agregados' => $agregados,
            'servicios completados' => $completados,
            'normas por servicio' => DB::table('norma_servicio')->insertOrIgnore($normas),
        ];
    }

    /**
     * Devuelve el id del registro con ese nombre, creándolo si no existe.
     *
     * @param  array<string, mixed>  $extra
     */
    private static function asegurar(string $tabla, string $nombre, array $extra = []): int
    {
        self::$ids[$tabla] ??= DB::table($tabla)->pluck('id', 'nombre')
            ->mapWithKeys(fn ($id, $n) => [self::clave($n) => $id])
            ->all();

        $clave = self::clave($nombre);

        if (! isset(self::$ids[$tabla][$clave])) {
            self::$ids[$tabla][$clave] = DB::table($tabla)->insertGetId($extra + [
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            self::$agregados++;
        }

        return self::$ids[$tabla][$clave];
    }

    private static function clave(string $nombre): string
    {
        return mb_strtolower(Str::ascii(trim($nombre)));
    }
}
