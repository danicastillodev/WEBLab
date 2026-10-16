<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspeciesSeeder extends Seeder
{
    /**
     * Las seis especies de ganado con las que arrancó el catálogo. Otros
     * seeders (RazasSeeder, FuncionesZootecnicasSeeder) las buscan por este
     * nombre, así que no se renombran.
     */
    public const BASE = ['Bovinos', 'Caprinos', 'Ovinos', 'Equinos', 'Porcinos', 'Aves'];

    /**
     * El resto del catálogo `catalogosespecies` del sistema anterior
     * (docs/old_db.sql). BOVINO, PORCINO, EQUINO, OVINO, CAPRINO y AVIAR ya
     * están en BASE con su nombre actual; aquí va todo lo demás, con
     * mayúsculas y acentos normalizados.
     */
    public const SISTEMA_ANTERIOR = [
        'Leche', 'Agua', 'Felino', 'Alimento', 'Ciervo', 'Roedor', 'N/A',
        'Canino', 'Antílope', 'Exótico', 'Nutria', 'Hurón', 'Tlacuache',
        'Oryx cimitarra', 'Reptil', 'Primate', 'Zorro', 'Pez', 'Hiénido',
        'Faisán', 'Venado', 'Cisne', 'Canguro', 'Quiróptero', 'Otro', 'Búfalo',
        'Asno', 'Conejo', 'Burro', 'Rana', 'Cobayo', 'Ovinos y caprinos',
        'Humano',
    ];

    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Inserta las especies que falten. Es idempotente: compara por nombre, que
     * es el índice único de la tabla.
     *
     * @return int cuántas especies se agregaron
     */
    public static function sembrar(): int
    {
        $existentes = DB::table('especies')->pluck('nombre')->flip();
        $now = now();

        $nuevas = collect([...self::BASE, ...self::SISTEMA_ANTERIOR])
            ->reject(fn ($nombre) => $existentes->has($nombre))
            ->map(fn ($nombre) => [
                'nombre' => $nombre,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::table('especies')->insert($nuevas);

        return count($nuevas);
    }
}
