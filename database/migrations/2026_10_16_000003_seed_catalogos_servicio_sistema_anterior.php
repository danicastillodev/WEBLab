<?php

use Database\Seeders\ServiciosSistemaAnteriorSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Completa métodos, normas, analistas (MVZ aprobados) y servicios con los
     * del sistema anterior (docs/old_db.sql). Va en una migración —y no solo
     * en el seeder— porque `composer run setup` corre migrate pero no db:seed.
     *
     * En las pruebas no se siembra: muchas asumen estos catálogos vacíos (la
     * numeración de servicios, los conteos). El seeder tiene su propia prueba.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        ServiciosSistemaAnteriorSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa a propósito: para este punto ya puede haber productos,
        // muestras y notas apuntando a estos servicios.
    }
};
