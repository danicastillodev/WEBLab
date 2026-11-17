<?php

namespace App\Http\Controllers;

use App\Models\DatosGeneral;
use App\Models\Diagnostico;
use App\Models\HistoriaClinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hoja de resultados (formato F070) de una historia clínica: una página por
 * cada análisis con diagnóstico emitido.
 */
class ReporteResultadosController extends Controller
{
    public function __invoke(Request $request, HistoriaClinica $historiaClinica): Response
    {
        $historiaClinica->load([
            'propietario:id,nombre,apellidos',
            'direccion.municipio:id,nombre',
            'direccion.estado:id,nombre',
            'explotacion.municipio:id,nombre',
            'explotacion.estado:id,nombre',
            'especie:id,nombre',
            'raza:id,nombre',
            'funcionZootecnica:id,nombre',
            'muestras' => fn ($q) => $q->orderBy('id'),
            'muestras.servicio:id,clave,siglas,descripcion,metodo_id,analista_id',
            'muestras.servicio.metodo:id,nombre',
            'muestras.servicio.normas:id,nombre,descripcion',
            'muestras.servicio.analista:id,nombre',
            'muestras.tipoMuestra:id,nombre',
            'muestras.diagnostico.muestras',
        ]);

        $emitidas = $historiaClinica->muestras
            ->filter(fn ($m) => $m->diagnostico?->estado === Diagnostico::ESTADO_EMITIDO)
            ->values();

        abort_if($emitidas->isEmpty(), 404, 'La historia clínica todavía no tiene resultados emitidos.');

        // Cada vez que se genera la hoja cuenta como una impresión de cada
        // diagnóstico, y la historia queda marcada como impresa: del todo si
        // ya están emitidos todos sus análisis no cancelados, si no, parcial.
        DB::transaction(function () use ($historiaClinica, $emitidas) {
            Diagnostico::whereIn('id', $emitidas->pluck('diagnostico.id'))->increment('impresiones');

            $porTrabajar = $historiaClinica->muestras
                ->reject(fn ($m) => $m->diagnostico?->estado === Diagnostico::ESTADO_CANCELADO)
                ->count();

            $historiaClinica->update([
                'impreso' => $emitidas->count() >= $porTrabajar
                    ? HistoriaClinica::IMPRESO_SI
                    : HistoriaClinica::IMPRESO_PARCIAL,
            ]);
        });

        $emitidas->each(fn ($m) => $m->diagnostico->impresiones++);

        return Inertia::render('HistoriasClinicas/Resultados', [
            'historia' => $historiaClinica->only([
                'id', 'numero_caso', 'fecha_recepcion', 'sexo', 'edad_unidad', 'edad_valor',
                'animales_explotacion', 'propietario', 'direccion', 'explotacion',
                'especie', 'raza', 'funcionZootecnica',
            ]),
            'analisis' => $emitidas,
            'datosGenerales' => DatosGeneral::with(['municipio:id,nombre', 'estado:id,nombre'])->first(),
            'usuario' => $request->user()->username ?? $request->user()->name,
            'fechaImpresion' => now()->format('d/m/Y H:i'),
        ]);
    }
}
