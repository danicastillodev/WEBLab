<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Agrega los clientes de facturación del sistema anterior.
 *
 * Los datos viven en database/data/clientes.json y salen de
 * `facturacionclientes` en docs/old_db.sql, ya depurados:
 *
 * - El RFC genérico (XAXX010101000 y sus variantes mal escritas) queda vacío;
 *   los demás, sin espacios.
 * - La dirección, que era texto libre, se partió en calle, número, colonia
 *   (lo que venía tras "COL." o "FRACC.") y código postal (tras "C.P.").
 * - Se unificaron los clientes repetidos por RFC o por nombre (con o sin punto
 *   final), quedándose con el registro más completo.
 *
 * `ids_anteriores` guarda los ids del sistema anterior, para cuando se migren
 * las facturas.
 */
class ClientesSistemaAnteriorSeeder extends Seeder
{
    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Es idempotente: un cliente se reconoce por su RFC o, si no tiene, por su
     * nombre.
     *
     * @return int cuántos clientes se agregaron
     */
    public static function sembrar(): int
    {
        $catalogo = json_decode(
            file_get_contents(database_path('data/clientes.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $estados = DB::table('estados')->pluck('id', 'clave');
        $municipios = DB::table('municipios')->get(['id', 'estado_id', 'nombre'])
            ->mapWithKeys(fn ($m) => [$m->estado_id.'|'.$m->nombre => $m->id]);

        $now = now();
        $agregados = 0;

        foreach ($catalogo as $c) {
            $existe = DB::table('clientes')
                ->when(
                    $c['rfc'],
                    fn ($q) => $q->where('rfc', $c['rfc']),
                    fn ($q) => $q->whereNull('rfc')->where('nombre', $c['nombre'])
                )
                ->exists();

            $estadoId = $estados[$c['estado']] ?? null;
            $municipioId = $municipios[$estadoId.'|'.$c['municipio']] ?? null;

            if ($existe || ! $municipioId) {
                continue;
            }

            DB::table('clientes')->insert([
                'nombre' => $c['nombre'],
                'rfc' => $c['rfc'],
                'telefono' => $c['telefono'],
                'calle' => $c['calle'],
                'numero_exterior' => $c['numero_exterior'],
                'colonia' => $c['colonia'] ?? '',
                'codigo_postal' => $c['codigo_postal'] ?? '',
                'estado_id' => $estadoId,
                'municipio_id' => $municipioId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $agregados++;
        }

        return $agregados;
    }
}
