<?php

use Database\Seeders\ExplotacionesSistemaAnteriorSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Reemplaza las explotaciones por las del sistema anterior
     * (docs/old_db.sql). Va en una migración —y no solo en el seeder— porque
     * `composer run setup` corre migrate pero no db:seed. Depende de que los
     * propietarios del sistema anterior ya estén sembrados.
     *
     * En las pruebas no se siembra: muchas asumen este catálogo vacío. El
     * seeder tiene su propia prueba.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        ExplotacionesSistemaAnteriorSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa: las explotaciones nuevas pueden quedar referenciadas
        // por historias clínicas.
    }
};
