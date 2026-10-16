<?php

namespace Database\Seeders;

use App\Models\Analista;
use App\Models\Categoria;
use App\Models\Norma;
use App\Models\TipoMuestra;
use Illuminate\Database\Seeder;

/**
 * Valores iniciales de los catálogos del alta de análisis, tomados del
 * sistema anterior. El resto se captura desde el propio formulario.
 */
class CatalogosServicioSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::firstOrCreate(['nombre' => 'GENERAL']);

        Analista::firstOrCreate(['nombre' => 'CENASA']);

        foreach (['ACUERDO CAMPAÑA TB', 'ACUERDO TB 2024/12/30', 'EMA BACTER TB', 'EMA BIOQUIMICA'] as $nombre) {
            Norma::firstOrCreate(['nombre' => $nombre]);
        }

        foreach (['ABEJAS EN ALCOHOL', 'ABSCESO', 'ADHERENCIA', 'AGUA', 'ALIMENTO'] as $nombre) {
            TipoMuestra::firstOrCreate(['nombre' => $nombre, 'prueba_id' => null]);
        }
    }
}
