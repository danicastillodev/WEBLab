<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportarDatos extends Command
{
    protected $signature = 'datos:exportar {--sin-migracion : Solo escribe el snapshot, sin generar la migración que lo aplica}';

    protected $description = 'Vuelca los catálogos de la BD local a database/data/sync para enviarlos al servidor de pruebas';

    /**
     * El snapshot se versiona en git; en el deploy, la migración generada
     * corre datos:importar y lo aplica sobre la BD del servidor.
     */
    public function handle(): int
    {
        $dir = DatosSincronizables::directorio();
        File::ensureDirectoryExists($dir);
        File::cleanDirectory($dir);

        $resumen = [];
        foreach (DatosSincronizables::TABLAS as $tabla) {
            $orden = collect(Schema::getIndexes($tabla))->firstWhere('primary', true)['columns'];
            $consulta = DB::table($tabla);
            foreach ($orden as $columna) {
                $consulta->orderBy($columna);
            }
            $filas = $consulta->get()->map(fn ($f) => (array) $f)->all();
            File::put("{$dir}/{$tabla}.json", json_encode($filas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");
            $resumen[$tabla] = count($filas);
            $this->line(sprintf('  %-24s %6d filas', $tabla, count($filas)));
        }

        // La firma depende solo del contenido: si nada cambió, el servidor
        // reconoce el snapshot y no vuelve a importarlo.
        $firma = hash('sha256', collect(DatosSincronizables::TABLAS)
            ->map(fn ($t) => File::get("{$dir}/{$t}.json"))
            ->implode(''));

        File::put("{$dir}/manifest.json", json_encode([
            'firma' => $firma,
            'tablas' => $resumen,
        ], JSON_PRETTY_PRINT)."\n");

        $this->info("Snapshot exportado en database/data/sync (firma {$firma}).");
        if (! $this->option('sin-migracion')) {
            $this->crearMigracion($firma);
        }

        return self::SUCCESS;
    }

    /**
     * El deploy solo corre `migrate --force`, así que el snapshot se aplica
     * desde una migración. Se crea una por snapshot distinto; si el contenido
     * no cambió desde la última exportación, no hace falta otra.
     */
    private function crearMigracion(string $firma): void
    {
        $corta = substr($firma, 0, 12);
        if (File::glob(database_path("migrations/*_sincroniza_datos_{$corta}.php"))) {
            $this->line('Sin cambios desde la última exportación: no se crea migración.');

            return;
        }

        $nombre = now()->format('Y_m_d_His')."_sincroniza_datos_{$corta}.php";
        File::put(database_path("migrations/{$nombre}"), <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

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

        Artisan::call('datos:importar', [], new \Symfony\Component\Console\Output\ConsoleOutput);
    }

    public function down(): void
    {
        // Los datos se quedan: no hay un estado anterior que reponer.
    }
};

PHP);
        $this->info("Migración creada: database/migrations/{$nombre}");
    }
}
