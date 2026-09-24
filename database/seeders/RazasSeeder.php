<?php

namespace Database\Seeders;

use App\Models\Especie;
use App\Models\Raza;
use Illuminate\Database\Seeder;

class RazasSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Bovinos' => [
                'Angus', 'Hereford', 'Charolais', 'Simmental', 'Brahman',
                'Holstein', 'Jersey', 'Pardo Suizo', 'Limousin', 'Gyr',
                'Nelore', 'Shorthorn', 'Beefmaster', 'Santa Gertrudis',
                'Brangus', 'Fleckvieh', 'Normando', 'Cebú', 'Criolla',
            ],
            'Caprinos' => [
                'Boer', 'Nubia', 'Saanen', 'Alpina Francesa', 'Toggenburg',
                'LaMancha', 'Angora', 'Kiko', 'Oberhasli',
                'Murciana-Granadina', 'Criolla',
            ],
            'Ovinos' => [
                'Merino', 'Suffolk', 'Dorper', 'Hampshire', 'Rambouillet',
                'Katahdin', 'Pelibuey', 'Blackbelly', 'Corriedale', 'Texel',
                'Columbia', 'Churra', 'Manchega', 'Romney', 'Criolla',
            ],
            'Equinos' => [
                'Cuarto de Milla', 'Pura Sangre Inglés', 'Árabe', 'Appaloosa',
                'Paint Horse', 'Palomino', 'Andaluz', 'Percherón', 'Frisón',
                'Mustang', 'Criollo', 'Azteca', 'Tennessee Walking Horse',
            ],
            'Porcinos' => [
                'Duroc', 'Yorkshire', 'Landrace', 'Hampshire', 'Berkshire',
                'Pietrain', 'Chester White', 'Poland China', 'Spotted',
                'Tamworth', 'Ibérico', 'Criolla',
            ],
            'Aves' => [
                'Broiler Ross 308', 'Broiler Cobb 500', 'Leghorn',
                'Rhode Island Red', 'Plymouth Rock', 'Sussex', 'Orpington',
                'Wyandotte', 'New Hampshire', 'Cornish', 'Brahma',
                'Codorniz Coturnix', 'Pavo Nicholas', 'Pato Pekín', 'Ganso',
            ],
        ];

        foreach ($catalogo as $especie => $razas) {
            $especieModel = Especie::where('nombre', $especie)->first();
            if (! $especieModel) {
                continue;
            }

            foreach ($razas as $nombre) {
                Raza::firstOrCreate([
                    'nombre' => $nombre,
                    'especie_id' => $especieModel->id,
                ]);
            }
        }
    }
}
