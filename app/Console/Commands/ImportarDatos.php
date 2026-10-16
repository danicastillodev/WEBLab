<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
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
            DB::transaction(function () use ($tablas, $datos, $referencias) {
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
                        try {
                            DB::table($tabla)->upsert($lote, ['id'], $actualizar);
                        } catch (UniqueConstraintViolationException) {
                            // Alguna fila choca con un índice único (p. ej. el
                            // mismo nombre con otro id en el servidor). Se
                            // resuelve fila por fila, fusionando la del servidor.
                            foreach ($lote as $fila) {
                                $viejos = $this->apartarDuplicados($tabla, $fila);
                                DB::table($tabla)->upsert([$fila], ['id'], $actualizar);
                                foreach ($viejos as $viejo) {
                                    foreach ($referencias[$tabla] ?? [] as $r) {
                                        $this->repuntar($r['tabla'], $r['columna'], $viejo, $fila['id']);
                                    }
                                    DB::table($tabla)->where('id', $viejo)->delete();
                                }
                            }
                        }
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
     * Si en el servidor hay otra fila (con otro id) que tiene los mismos
     * valores que $fila en algún índice único, es el mismo registro dado de
     * alta por separado. Se le cambian esos valores para que $fila pueda
     * entrar, y se devuelve su id: el llamador pasa sus referencias al id
     * local y la borra.
     *
     * @param  array<string, mixed>  $fila
     * @return list<int>
     */
    private function apartarDuplicados(string $tabla, array $fila): array
    {
        $viejos = [];
        $unicos = collect(Schema::getIndexes($tabla))->filter(fn ($i) => $i['unique'] && ! $i['primary']);
        foreach ($unicos as $indice) {
            $valores = array_intersect_key($fila, array_flip($indice['columns']));
            if (count($valores) !== count($indice['columns']) || in_array(null, $valores, true)) {
                continue;
            }
            foreach (DB::table($tabla)->where($valores)->where('id', '!=', $fila['id'])->pluck('id') as $viejo) {
                DB::table($tabla)->where('id', $viejo)->update(
                    array_map(fn () => "~fusion-{$viejo}", array_flip($indice['columns']))
                );
                $viejos[] = $viejo;
            }
        }

        return array_values(array_unique($viejos));
    }

    /**
     * Cambia $columna de $viejo a $nuevo. Si con eso una fila queda repetida
     * en un índice único (una pivote que ya tenía las dos), sobra y se borra.
     */
    private function repuntar(string $tabla, string $columna, int $viejo, int $nuevo): void
    {
        try {
            DB::table($tabla)->where($columna, $viejo)->update([$columna => $nuevo]);
        } catch (UniqueConstraintViolationException) {
            $clave = Schema::hasColumn($tabla, 'id') ? 'id' : $columna;
            foreach (DB::table($tabla)->where($columna, $viejo)->get() as $fila) {
                $filtro = $clave === 'id' ? ['id' => $fila->id] : (array) $fila;
                try {
                    DB::table($tabla)->where($filtro)->update([$columna => $nuevo]);
                } catch (UniqueConstraintViolationException) {
                    DB::table($tabla)->where($filtro)->delete();
                }
            }
        }
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
