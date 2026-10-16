<?php

namespace Database\Seeders;

use App\Models\Servicio;
use App\Models\TipoMuestra;
use Illuminate\Database\Seeder;

/**
 * Tipos de muestra de ejemplo para cada análisis, de forma que el selector
 * "Tipo de muestra" del alta de historias clínicas tenga opciones. Se asignan
 * por la clave del servicio; el que no coincida con ninguna regla recibe
 * ÓRGANOS. Solo agrega: no quita lo que ya se haya elegido en el catálogo.
 */
class TiposMuestraServiciosSeeder extends Seeder
{
    /**
     * Expresión sobre la clave del servicio => tipos de muestra. Gana la
     * primera regla que coincida.
     *
     * @var array<string, list<string>>
     */
    private const REGLAS = [
        '/^BTA24$/' => ['AGUA'],
        '/^BTA25$/' => ['ABSCESO', 'HISOPO'],
        '/^BTG23$/' => ['ABSCESO', 'ÓRGANOS', 'HISOPO'],
        '/^BTGM$/' => ['MIEL'],
        '/^BTM69$/' => ['ABSCESO', 'HISOPO'],
        '/^BTS(22)?$/' => ['HECES', 'ALIMENTO'],
        '/^BTS23$/' => ['HISOPO'],
        '/^BTS68$/' => ['ÓRGANOS'],
        '/^CENT$/' => ['SUERO SANGUÍNEO'],
        '/^CLC63$/' => ['HECES'],
        '/^CLH(19|20)$/' => ['SANGRE CON EDTA'],
        '/^(EEE|PTR56)$/' => ['ENCÉFALO'],
        '/^(MTM01|MTPB)$/' => ['HISOPO'],
        '/^PAO33$/' => ['ABSCESO', 'HISOPO'],
        '/^PAS18$/' => ['ABEJAS EN ALCOHOL'],
        '/^PCR/' => ['HISOPO', 'ÓRGANOS'],
        '/^PTC52$/' => ['ADHERENCIA', 'ABSCESO'],
        '/^PTE/' => ['RESIDUOS BIOLÓGICOS'],
        '/^PTN/' => ['ANIMAL COMPLETO'],
        '/^(PTH|PTL|PTI|PTT|TBA|TBT)/' => ['ÓRGANOS'],
        '/^SEE4$/' => ['FLUIDOS ORALES'],
        '/^SE/' => ['SUERO SANGUÍNEO'],
        '/^TXC/' => ['ORINA', 'ALIMENTO'],
    ];

    private const POR_DEFECTO = ['ÓRGANOS'];

    public function run(): void
    {
        $tipos = [];
        $tipoId = function (string $nombre) use (&$tipos): int {
            return $tipos[$nombre] ??= TipoMuestra::firstOrCreate(['nombre' => $nombre, 'prueba_id' => null])->id;
        };

        foreach (Servicio::all() as $servicio) {
            $nombres = self::POR_DEFECTO;
            foreach (self::REGLAS as $patron => $asignados) {
                if (preg_match($patron, $servicio->clave)) {
                    $nombres = $asignados;
                    break;
                }
            }

            $servicio->tiposMuestra()->syncWithoutDetaching(array_map($tipoId, $nombres));
        }
    }
}
