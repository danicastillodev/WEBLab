<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Una muestra puede facturarse en varias notas hasta completar su
     * cantidad, así que lo ya facturado se suma por muestra_id. Los conceptos
     * guardados antes de existir esa columna se generaron uno por muestra, en
     * el orden de las muestras de su historia: se emparejan por posición
     * cuando el número de conceptos coincide con el de muestras.
     */
    public function up(): void
    {
        $grupos = DB::table('nota_pago_conceptos')
            ->whereNull('muestra_id')
            ->whereNotNull('historia_clinica_id')
            ->select('nota_pago_id', 'historia_clinica_id')
            ->distinct()
            ->get();

        foreach ($grupos as $grupo) {
            $conceptos = DB::table('nota_pago_conceptos')
                ->where('nota_pago_id', $grupo->nota_pago_id)
                ->where('historia_clinica_id', $grupo->historia_clinica_id)
                ->whereNull('muestra_id')
                ->orderBy('id')
                ->pluck('id');

            $muestras = DB::table('muestras')
                ->where('historia_clinica_id', $grupo->historia_clinica_id)
                ->orderBy('id')
                ->pluck('id');

            if ($conceptos->count() !== $muestras->count()) {
                continue;
            }

            foreach ($conceptos as $i => $conceptoId) {
                DB::table('nota_pago_conceptos')->where('id', $conceptoId)->update(['muestra_id' => $muestras[$i]]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible a propósito: no se distingue qué muestra_id vino de
        // este relleno y cuál se guardó al facturar.
    }
};
