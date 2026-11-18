<?php

namespace App\Console\Commands;

use App\Models\HistoriaClinica;
use Illuminate\Console\Command;

class ActualizarEstadosHistorias extends Command
{
    protected $signature = 'weblab:actualizar-estados-historias';

    protected $description = 'Recalcula el estado (concluida / resultado parcial) de las historias clínicas según sus diagnósticos';

    /**
     * Para las historias que ya tenían diagnósticos antes de que el estado se
     * actualizara solo al guardar, emitir o cancelar. Se puede correr las
     * veces que sea: deja igual las que ya están bien.
     */
    public function handle(): int
    {
        $cambios = [];
        $revisadas = 0;

        HistoriaClinica::whereHas('muestras.diagnostico')
            ->where('estado', '!=', HistoriaClinica::ESTADO_CANCELADA)
            ->chunkById(200, function ($historias) use (&$cambios, &$revisadas) {
                foreach ($historias as $historia) {
                    $antes = $historia->estado;
                    $historia->actualizarEstadoPorDiagnosticos();
                    $revisadas++;

                    if ($historia->estado !== $antes) {
                        $clave = "{$antes} → {$historia->estado}";
                        $cambios[$clave] = ($cambios[$clave] ?? 0) + 1;
                    }
                }
            });

        $this->info("Historias revisadas: {$revisadas}. Actualizadas: ".array_sum($cambios).'.');
        foreach ($cambios as $cambio => $total) {
            $this->line("  {$cambio}: {$total}");
        }

        return self::SUCCESS;
    }
}
