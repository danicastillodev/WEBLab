<?php

use Database\Seeders\EspeciesSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Completa el catálogo de especies con las del sistema anterior
     * (`catalogosespecies` en docs/old_db.sql). Va en una migración —y no solo
     * en el seeder— porque `composer run setup` corre migrate pero no db:seed.
     */
    public function up(): void
    {
        EspeciesSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa a propósito: para este punto ya puede haber razas,
        // pruebas e historias clínicas apuntando a estas especies.
    }
};
