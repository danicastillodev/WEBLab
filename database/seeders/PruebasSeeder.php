<?php

namespace Database\Seeders;

use App\Models\Especie;
use App\Models\Prueba;
use App\Models\TipoMuestra;
use Illuminate\Database\Seeder;

class PruebasSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Bovinos' => [
                'Brucelosis tarjeta (Rosa de Bengala)' => ['Suero sanguíneo'],
                'Brucelosis FPA' => ['Suero sanguíneo'],
                'Brucelosis 2-Mercaptoetanol' => ['Suero sanguíneo'],
                'Tuberculosis PPD' => ['Animal en pie'],
                'Leucosis Bovina AGID' => ['Suero sanguíneo'],
                'Leucosis Bovina ELISA' => ['Suero sanguíneo'],
                'Leptospirosis MAT' => ['Suero sanguíneo'],
                'IBR ELISA' => ['Suero sanguíneo'],
                'DVB ELISA' => ['Suero sanguíneo', 'Sangre con EDTA'],
                'Neosporosis ELISA' => ['Suero sanguíneo'],
                'Mastitis CMT' => ['Leche'],
                'Paratuberculosis ELISA' => ['Suero sanguíneo', 'Heces'],
            ],
            'Caprinos' => [
                'Brucelosis tarjeta (Rosa de Bengala)' => ['Suero sanguíneo'],
                'Brucelosis FPA' => ['Suero sanguíneo'],
                'CAEV AGID' => ['Suero sanguíneo'],
                'CAEV ELISA' => ['Suero sanguíneo'],
                'Leptospirosis MAT' => ['Suero sanguíneo'],
            ],
            'Ovinos' => [
                'Brucelosis tarjeta (Rosa de Bengala)' => ['Suero sanguíneo'],
                'Brucelosis FPA' => ['Suero sanguíneo'],
                'Maedi-Visna AGID' => ['Suero sanguíneo'],
                'Lengua Azul ELISA' => ['Suero sanguíneo'],
                'Leptospirosis MAT' => ['Suero sanguíneo'],
            ],
            'Equinos' => [
                'Anemia Infecciosa Equina AGID' => ['Suero sanguíneo'],
                'Arteritis Viral Equina SN' => ['Suero sanguíneo'],
                'Muermo FC' => ['Suero sanguíneo'],
                'Influenza Equina HI' => ['Suero sanguíneo'],
                'Herpesvirus Equino ELISA' => ['Suero sanguíneo'],
                'Leptospirosis MAT' => ['Suero sanguíneo'],
            ],
            'Porcinos' => [
                'PRRS ELISA' => ['Suero sanguíneo', 'Sangre con EDTA'],
                'Peste Porcina Clásica ELISA' => ['Suero sanguíneo'],
                'Brucelosis tarjeta (Rosa de Bengala)' => ['Suero sanguíneo'],
                'Enfermedad de Aujeszky ELISA' => ['Suero sanguíneo'],
                'Leptospirosis MAT' => ['Suero sanguíneo'],
                'Influenza Porcina HI' => ['Suero sanguíneo', 'Hisopo nasofaríngeo'],
            ],
            'Aves' => [
                'Influenza Aviar AGID' => ['Suero sanguíneo'],
                'Influenza Aviar HI' => ['Suero sanguíneo', 'Hisopo traqueal', 'Hisopo cloacal'],
                'Newcastle HI' => ['Suero sanguíneo'],
                'Newcastle ELISA' => ['Suero sanguíneo'],
                'Bronquitis Infecciosa HI' => ['Suero sanguíneo'],
                'Mycoplasma gallisepticum ELISA' => ['Suero sanguíneo'],
                'Mycoplasma synoviae ELISA' => ['Suero sanguíneo'],
                'Salmonella aglutinación' => ['Suero sanguíneo', 'Heces'],
                'Laringotraqueítis ELISA' => ['Suero sanguíneo'],
                'Marek PCR' => ['Sangre con EDTA', 'Folículo de pluma'],
            ],
        ];

        foreach ($catalogo as $especie => $pruebas) {
            $especieModel = Especie::where('nombre', $especie)->first();
            if (! $especieModel) {
                continue;
            }

            foreach ($pruebas as $nombrePrueba => $tiposMuestra) {
                $prueba = Prueba::firstOrCreate([
                    'nombre' => $nombrePrueba,
                    'especie_id' => $especieModel->id,
                ]);

                foreach ($tiposMuestra as $tipoNombre) {
                    TipoMuestra::firstOrCreate([
                        'prueba_id' => $prueba->id,
                        'nombre' => $tipoNombre,
                    ]);
                }
            }
        }
    }
}
