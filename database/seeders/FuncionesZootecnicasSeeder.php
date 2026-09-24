<?php

namespace Database\Seeders;

use App\Models\Especie;
use App\Models\FuncionZootecnica;
use Illuminate\Database\Seeder;

class FuncionesZootecnicasSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            'Bovinos' => [
                'Carne', 'Leche', 'Doble propósito', 'Reproducción', 'Pie de cría', 'Trabajo',
            ],
            'Caprinos' => [
                'Carne', 'Leche', 'Doble propósito', 'Pie de cría', 'Fibra',
            ],
            'Ovinos' => [
                'Carne', 'Lana', 'Leche', 'Doble propósito', 'Pie de cría',
            ],
            'Equinos' => [
                'Trabajo', 'Deporte', 'Reproducción', 'Pie de cría', 'Recreación', 'Carga',
            ],
            'Porcinos' => [
                'Engorda', 'Reproducción', 'Pie de cría', 'Carne',
            ],
            'Aves' => [
                'Postura', 'Engorda', 'Pie de cría', 'Doble propósito', 'Reproducción', 'Ornamental',
            ],
        ];

        foreach ($catalogo as $especie => $funciones) {
            $especieModel = Especie::where('nombre', $especie)->first();
            if (! $especieModel) {
                continue;
            }

            foreach ($funciones as $nombre) {
                FuncionZootecnica::firstOrCreate([
                    'nombre' => $nombre,
                    'especie_id' => $especieModel->id,
                ]);
            }
        }
    }
}
