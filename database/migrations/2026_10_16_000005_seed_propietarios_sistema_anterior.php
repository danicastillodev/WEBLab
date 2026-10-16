<?php

use Database\Seeders\PropietariosSistemaAnteriorSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Agrega los propietarios del sistema anterior, unificados y con sus
     * direcciones (docs/old_db.sql). Va en una migración —y no solo en el
     * seeder— porque `composer run setup` corre migrate pero no db:seed.
     * Depende de que los municipios ya estén sembrados.
     *
     * En las pruebas no se siembra: muchas asumen este catálogo vacío. El
     * seeder tiene su propia prueba.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        PropietariosSistemaAnteriorSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa a propósito: para este punto ya puede haber historias
        // clínicas y notas apuntando a estos propietarios.
    }
};
