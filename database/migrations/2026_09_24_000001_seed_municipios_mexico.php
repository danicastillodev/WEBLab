<?php

use Database\Seeders\MunicipiosSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Completa el catálogo de municipios.
     *
     * La migración que crea la tabla solo sembró Jalisco. Acá se agregan los de
     * los 32 estados, desde database/data/municipios.json. Se hace en una
     * migración —y no solo en el seeder— porque `composer run setup` corre
     * migrate pero no db:seed, y los catálogos deben quedar listos en una
     * instalación limpia.
     */
    public function up(): void
    {
        MunicipiosSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa a propósito: para este punto ya puede haber direcciones y
        // explotaciones apuntando a estos municipios, y borrarlos las tiraría
        // por el cascadeOnDelete de la tabla.
    }
};
