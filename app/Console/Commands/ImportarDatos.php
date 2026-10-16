<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ImportarDatos extends Command
{
    protected $signature = 'datos:importar {--force : Importa aunque el snapshot ya se haya aplicado}';

    protected $description = 'Aplica el snapshot de database/data/sync sobre la BD (lo corren las migraciones *_sincroniza_datos_* en el deploy)';

    public function handle(): int
    {
        $dir = DatosSincronizables::directorio();
        if (! File::exists("{$dir}/manifest.json")) {
            $this->line('Sin snapshot en database/data/sync; nada que importar.');

            return self::SUCCESS;
        }

        $manifest = json_decode(File::get("{$dir}/manifest.json"), true);
        $marca = storage_path('app/datos_sync_firma');
        if (! $this->option('force') && File::exists($marca) && trim(File::get($marca)) === $manifest['firma']) {
            $this->line('El snapshot ya estaba aplicado; nada que importar.');

            return self::SUCCESS;
        }

        $tablas = array_keys($manifest['tablas']);
        $datos = [];
        foreach ($tablas as $tabla) {
            $datos[$tabla] = json_decode(File::get("{$dir}/{$tabla}.json"), true);
        }

        // Bajas: lo que ya no existe en local se borra, salvo que alguna fila
        // lo siga referenciando. Varias FK borran en cascada (propietarios →
        // historias_clinicas, areas → area_user), así que borrar algo en uso
        // se llevaría por delante historias, notas de pago o usuarios del
        // servidor. Se va de hijos a padres: un padre queda libre cuando sus
        // hijos sobrantes ya se fueron. Va antes de las altas para liberar
        // valores únicos (nombres, claves) que las filas nuevas puedan traer.
        $referencias = $this->referenciasEntrantes();
        $conservadas = 0;
        foreach (array_reverse($tablas) as $tabla) {
            if (! Schema::hasColumn($tabla, 'id')) {
                continue; // Las tablas pivote se reponen enteras más abajo.
            }
            $ids = array_column($datos[$tabla], 'id');
            $sobrantes = DB::table($tabla)->whereNotIn('id', $ids ?: [0])->pluck('id');
            if ($sobrantes->isNotEmpty()) {
                $enUso = collect($referencias[$tabla] ?? [])
                    ->flatMap(fn ($r) => $sobrantes->chunk(500)->flatMap(
                        fn ($lote) => DB::table($r['tabla'])->whereIn($r['columna'], $lote)->pluck($r['columna'])
                    ))
                    ->unique();
                $borrables = $sobrantes->diff($enUso);
                foreach ($borrables->chunk(500) as $lote) {
                    DB::table($tabla)->whereIn('id', $lote)->delete();
                }
                $conservadas += $sobrantes->count() - $borrables->count();
            }
        }

        // Altas y cambios por id, sin revisar claves foráneas: así el orden
        // entre tablas no importa y las referencias se resuelven al terminar.
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($tablas, $datos) {
                foreach ($tablas as $tabla) {
                    $this->line(sprintf('  %-24s %6d filas', $tabla, count($datos[$tabla])));
                    $columnas = Schema::getColumnListing($tabla);
                    if (! in_array('id', $columnas)) {
                        // Pivote sin id (norma_servicio, servicio_tipo_muestra):
                        // nadie la referencia, así que se reemplaza completa.
                        DB::table($tabla)->delete();
                        foreach (array_chunk($datos[$tabla], 500) as $lote) {
                            DB::table($tabla)->insert($lote);
                        }

                        continue;
                    }
                    foreach (array_chunk($datos[$tabla], 500) as $lote) {
                        $lote = array_map(fn ($f) => array_intersect_key($f, array_flip($columnas)), $lote);
                        $actualizar = array_values(array_diff(array_keys($lote[0]), ['id']));
                        DB::table($tabla)->upsert($lote, ['id'], $actualizar);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        File::put($marca, $manifest['firma']);
        $this->info('Snapshot importado'.($conservadas ? " ({$conservadas} filas en uso se conservaron)" : '').'.');

        return self::SUCCESS;
    }

    /**
     * Para cada tabla, las columnas (de cualquier tabla de la BD) que la
     * referencian por clave foránea.
     *
     * @return array<string, list<array{tabla: string, columna: string}>>
     */
    private function referenciasEntrantes(): array
    {
        $referencias = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $tabla) {
            foreach (Schema::getForeignKeys($tabla) as $fk) {
                if (count($fk['columns']) === 1) {
                    $referencias[$fk['foreign_table']][] = ['tabla' => $tabla, 'columna' => $fk['columns'][0]];
                }
            }
        }

        return $referencias;
    }
}
