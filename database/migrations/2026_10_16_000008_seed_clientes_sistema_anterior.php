<?php

use Database\Seeders\ClientesSistemaAnteriorSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Agrega los clientes de facturación del sistema anterior
     * (docs/old_db.sql). Va en una migración —y no solo en el seeder— porque
     * `composer run setup` corre migrate pero no db:seed. Depende de que los
     * municipios ya estén sembrados.
     *
     * En las pruebas no se siembra: muchas asumen este catálogo vacío. El
     * seeder tiene su propia prueba.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        ClientesSistemaAnteriorSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa a propósito: para este punto ya puede haber notas de
        // pago apuntando a estos clientes.
    }
};
