<?php

namespace Database\Seeders;

use App\Models\Especie;
use Illuminate\Database\Seeder;

class EspeciesSeeder extends Seeder
{
    public function run(): void
    {
        $especies = ['Bovinos', 'Caprinos', 'Ovinos', 'Equinos', 'Porcinos', 'Aves'];

        foreach ($especies as $nombre) {
            Especie::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
