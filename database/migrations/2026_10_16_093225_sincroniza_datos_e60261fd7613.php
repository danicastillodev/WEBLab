<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Generada por `php artisan datos:exportar`: aplica en el servidor el
 * snapshot de catálogos de database/data/sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Las pruebas arman sus propios datos; el snapshot real no les sirve.
        if (app()->environment('testing')) {
            return;
        }

        Artisan::call('datos:importar', [], new ConsoleOutput);
    }

    public function down(): void
    {
        // Los datos se quedan: no hay un estado anterior que reponer.
    }
};
