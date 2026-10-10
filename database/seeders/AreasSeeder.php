<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Bacteriología',
            'Brucella',
            'Patología',
            'PCR',
            'Recepción',
            'Serología',
            'Toxicología',
            'Tuberculosis',
            'Virología',
        ];

        foreach ($areas as $nombre) {
            Area::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
