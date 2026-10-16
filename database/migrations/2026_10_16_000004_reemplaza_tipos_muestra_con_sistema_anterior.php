<?php

use Database\Seeders\TiposMuestraServiciosSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Reemplaza los tipos de muestra, y su relación con los análisis, por los
     * del sistema anterior (docs/old_db.sql). Va en una migración —y no solo
     * en el seeder— porque `composer run setup` corre migrate pero no db:seed.
     * Depende de que los servicios del sistema anterior ya estén sembrados.
     *
     * En las pruebas no se siembra, igual que los servicios: muchas asumen
     * este catálogo vacío.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        TiposMuestraServiciosSeeder::sembrar();
    }

    public function down(): void
    {
        // Sin reversa: los tipos borrados eran de ejemplo y los nuevos pueden
        // quedar referenciados por muestras.
    }
};
