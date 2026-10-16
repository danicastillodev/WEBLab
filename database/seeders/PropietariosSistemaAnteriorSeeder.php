<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Agrega los propietarios del sistema anterior con sus direcciones.
 *
 * Los datos viven en database/data/propietarios.json y salen de
 * `catalogospropietarios` en docs/old_db.sql, ya depurados:
 *
 * - En el sistema anterior, para darle otra dirección a un propietario se
 *   capturaba de nuevo con un punto al final ("JUAN PEREZ."). Esos registros
 *   se unificaron en uno solo y cada uno aportó su dirección.
 * - El nombre completo se separó en nombre y apellidos: los dos últimos
 *   apellidos (con sus partículas: DE LA TORRE, DEL TORO). Las empresas y los
 *   registros de varias personas ("Y/O") quedan completos en `nombre`.
 * - La dirección, que era texto libre, se partió en calle, número y colonia
 *   cuando traía número (#, No., N°); si no, todo va en la calle con S/N.
 *
 * `ids_anteriores` e `id_anterior` guardan los ids del sistema anterior, para
 * cuando se migren explotaciones e historias clínicas.
 */
class PropietariosSistemaAnteriorSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Es idempotente: un propietario se reconoce por nombre y apellidos, y una
     * dirección por calle, número, colonia y municipio.
     *
     * @return array{propietarios: int, direcciones: int}
     */
    public static function sembrar(): array
    {
        $catalogo = json_decode(
            file_get_contents(database_path('data/propietarios.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $estados = DB::table('estados')->pluck('id', 'clave');
        $municipios = DB::table('municipios')->get(['id', 'estado_id', 'nombre'])
            ->mapWithKeys(fn ($m) => [$m->estado_id.'|'.$m->nombre => $m->id]);

        $now = now();
        $propietarios = 0;
        $direcciones = 0;

        foreach ($catalogo as $p) {
            $propietarioId = DB::table('propietarios')
                ->where('nombre', $p['nombre'])->where('apellidos', $p['apellidos'])
                ->value('id');

            if (! $propietarioId) {
                $propietarioId = DB::table('propietarios')->insertGetId([
                    'nombre' => $p['nombre'],
                    'apellidos' => $p['apellidos'],
                    'telefono' => $p['telefono'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $propietarios++;
            }

            foreach ($p['direcciones'] as $d) {
                $estadoId = $estados[$d['estado']] ?? null;
                $municipioId = $municipios[$estadoId.'|'.$d['municipio']] ?? null;

                if (! $municipioId) {
                    continue;
                }

                $existe = DB::table('direcciones')
                    ->where('propietario_id', $propietarioId)
                    ->where('calle', $d['calle'])
                    ->where('numero_exterior', $d['numero_exterior'])
                    ->where('colonia', $d['colonia'])
                    ->where('municipio_id', $municipioId)
                    ->exists();

                if ($existe) {
                    continue;
                }

                DB::table('direcciones')->insert([
                    'propietario_id' => $propietarioId,
                    'calle' => $d['calle'],
                    'numero_exterior' => $d['numero_exterior'],
                    'colonia' => $d['colonia'],
                    'municipio_id' => $municipioId,
                    'estado_id' => $estadoId,
                    'codigo_postal' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $direcciones++;
            }
        }

        return ['propietarios' => $propietarios, 'direcciones' => $direcciones];
    }
}
