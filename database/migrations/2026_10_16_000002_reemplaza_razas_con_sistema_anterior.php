<?php

use Database\Seeders\RazasSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Reemplaza el catálogo de razas por el del sistema anterior
     * (`catalogosrazas` en docs/old_db.sql). Va en una migración —y no solo
     * en el seeder— porque `composer run setup` corre migrate pero no db:seed.
     * Depende de que las especies del sistema anterior ya estén sembradas.
     */
    public function up(): void
    {
        RazasSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa: las razas borradas eran datos de ejemplo y las nuevas
        // pueden quedar referenciadas por historias clínicas.
    }
};
