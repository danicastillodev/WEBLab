<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\ParametroOperacion;
use App\Models\Propietario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NotasPagoController extends Controller
{
    public function create(Request $request): Response
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        return Inertia::render('NotasPago/Create', [
            'propietarios' => Propietario::orderBy('apellidos')->orderBy('nombre')->get(['id', 'nombre', 'apellidos', 'telefono', 'rfc']),
            'direcciones' => Direccion::with(['municipio', 'estado'])->get(['id', 'propietario_id', 'calle', 'numero_exterior', 'numero_interior', 'colonia', 'municipio_id', 'estado_id', 'codigo_postal', 'caseta', 'lote', 'parvada']),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
            'conceptos_opciones' => NotaPago::CONCEPTOS,
            'tipos_pago' => NotaPago::TIPOS_PAGO,
            'iva_porcentaje' => ParametroOperacion::first()?->iva_porcentaje ?? 16,
            'historias_preseleccionadas' => $request->boolean('preseleccionar_hoy')
                ? $this->historiasCapturadasHoySinNotaPago($request)
                : null,
        ]);
    }

    /**
     * Historias clínicas capturadas el día de hoy por el usuario actual que
     * todavía no están ligadas a ninguna nota de pago — usado para
     * preseleccionarlas al llegar desde "¿Desea crear una nota de pago?" en
     * el alta de una historia clínica.
     */
    private function historiasCapturadasHoySinNotaPago(Request $request): ?array
    {
        $historias = HistoriaClinica::with(['muestras.servicio', 'muestras.producto'])
            ->where('created_by', $request->user()->id)
            ->whereDate('created_at', today())
            ->whereDoesntHave('notasPago')
            ->orderBy('numero_caso')
            ->get();

        if ($historias->isEmpty()) {
            return null;
        }

        $primera = $historias->first();

        return [
            'historias' => $historias->map(fn ($h) => ['id' => $h->id, 'numero_caso' => $h->numero_caso])->values(),
            'propietario_id' => $primera->propietario_id,
            'direccion_id' => $primera->direccion_id,
            'conceptos' => $this->construirConceptosDesdeHistorias($historias),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $validated = $request->validate([
            'propietario_id' => ['required', 'integer', 'exists:propietarios,id'],
            'direccion_id' => ['nullable', 'integer', 'exists:direcciones,id'],
            'historia_clinica_ids' => ['required', 'array', 'min:1'],
            'historia_clinica_ids.*' => ['integer', 'exists:historias_clinicas,id'],
            'concepto' => ['required', 'string', 'in:'.implode(',', NotaPago::CONCEPTOS)],
            'tipo_pago' => ['required', 'string', 'in:'.implode(',', NotaPago::TIPOS_PAGO)],
            'pagada' => ['boolean'],
            'notas_adicionales' => ['nullable', 'string', 'max:1000'],
        ]);

        // Los conceptos nunca se confían al cliente: se reconstruyen aquí a
        // partir de las muestras de las historias clínicas seleccionadas,
        // igual que en conceptosDeHistorias(), para que no sea posible
        // manipular cantidades o precios desde el navegador.
        $historias = HistoriaClinica::with(['muestras.servicio', 'muestras.producto'])
            ->whereIn('id', $validated['historia_clinica_ids'])
            ->get();

        if ($historias->count() !== count(array_unique($validated['historia_clinica_ids']))) {
            abort(404, 'Una o más historias clínicas seleccionadas no existen o están repetidas.');
        }

        $yaLigada = $historias->first(fn ($h) => $h->notasPago()->exists());
        if ($yaLigada) {
            return back()->withErrors([
                'historia_clinica_ids' => "La historia clínica (caso {$yaLigada->numero_caso}) ya está ligada a otra nota de pago.",
            ])->withInput();
        }

        // El IVA nunca se confía al cliente: se toma el porcentaje vigente en
        // Configuración General → Parámetros de Operación en este instante
        // (16% es el mismo valor por defecto que usa ParametrosOperacionController
        // cuando todavía no existe el registro de parámetros).
        $ivaPorcentaje = (float) (ParametroOperacion::first()?->iva_porcentaje ?? 16);

        $notaPago = DB::transaction(function () use ($validated, $historias, $ivaPorcentaje) {
            $conceptos = $this->construirConceptosDesdeHistorias($historias)->map(fn ($c) => [
                'producto_id' => $c['producto_id'],
                'descripcion' => $c['descripcion'],
                'cantidad' => $c['cantidad'],
                'precio_unitario' => $c['precio_unitario'],
                'importe' => round($c['cantidad'] * $c['precio_unitario'], 2),
            ]);

            $subtotal = round((float) $conceptos->sum('importe'), 2);
            $ivaMonto = round($subtotal * $ivaPorcentaje / 100, 2);

            $notaPago = NotaPago::create([
                'folio' => NotaPago::siguienteFolio(),
                'propietario_id' => $validated['propietario_id'],
                'direccion_id' => $validated['direccion_id'] ?? null,
                'concepto' => $validated['concepto'],
                'tipo_pago' => $validated['tipo_pago'],
                'pagada' => $validated['pagada'] ?? false,
                'subtotal' => $subtotal,
                'iva_porcentaje' => $ivaPorcentaje,
                'iva_monto' => $ivaMonto,
                'total' => round($subtotal + $ivaMonto, 2),
                'notas_adicionales' => $validated['notas_adicionales'] ?? null,
            ]);

            $notaPago->conceptos()->createMany($conceptos->all());
            $notaPago->historiasClinicas()->attach($historias->pluck('id'));

            return $notaPago;
        });

        return redirect()->route('notas-pago.create')
            ->with('success', "Nota de pago folio {$notaPago->folio} creada.");
    }

    /**
     * Lista de historias clínicas para el selector "Seleccionar Historias
     * Clínicas" del formulario — filtrable por número de caso o nombre del
     * propietario.
     */
    public function buscarHistorias(Request $request): JsonResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $buscar = trim((string) $request->input('buscar', ''));

        $historias = HistoriaClinica::with('propietario')
            // Una historia ya ligada a una nota de pago no vuelve a ofrecerse:
            // no tendría sentido facturarla dos veces.
            ->whereDoesntHave('notasPago')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($sq) use ($buscar) {
                    if (ctype_digit($buscar)) {
                        $sq->orWhere('numero_caso', (int) $buscar);
                    }
                    $sq->orWhereHas('propietario', fn ($pq) => $pq->whereRaw(
                        "CONCAT(nombre, ' ', apellidos) LIKE ?",
                        ["%{$buscar}%"]
                    ));
                });
            })
            ->latest('fecha_recepcion')
            ->limit(20)
            ->get(['id', 'numero_caso', 'ejercicio', 'fecha_recepcion', 'propietario_id']);

        return response()->json([
            'historias' => $historias->map(fn ($h) => [
                'id' => $h->id,
                'numero_caso' => $h->numero_caso,
                'ejercicio' => $h->ejercicio,
                'fecha_recepcion' => $h->fecha_recepcion,
                'propietario' => trim(($h->propietario?->nombre ?? '').' '.($h->propietario?->apellidos ?? '')),
            ]),
        ]);
    }

    /**
     * Conceptos sugeridos a partir de las muestras de una o varias historias
     * clínicas seleccionadas, para el botón "Seleccionar Historias Clínicas"
     * del formulario.
     */
    public function conceptosDeHistorias(Request $request): JsonResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $validated = $request->validate([
            'historia_clinica_ids' => ['required', 'array', 'min:1'],
            'historia_clinica_ids.*' => ['integer', 'exists:historias_clinicas,id'],
        ]);

        $historias = HistoriaClinica::with(['muestras.servicio', 'muestras.producto'])
            ->whereIn('id', $validated['historia_clinica_ids'])
            ->orderBy('numero_caso')
            ->get();

        if ($historias->isEmpty()) {
            return response()->json(['message' => 'No se encontraron las historias clínicas seleccionadas.'], 404);
        }

        $primera = $historias->first();

        return response()->json([
            'historias' => $historias->map(fn ($h) => ['id' => $h->id, 'numero_caso' => $h->numero_caso])->values(),
            'propietario_id' => $primera->propietario_id,
            'direccion_id' => $primera->direccion_id,
            'conceptos' => $this->construirConceptosDesdeHistorias($historias),
        ]);
    }

    /**
     * Conceptos (uno por muestra) de un conjunto de historias clínicas, con
     * el precio exacto que venga del producto vinculado a cada prueba
     * ("Tipo de muestra" en la captura de la historia clínica) — 0 cuando la
     * muestra no tiene un producto capturado.
     *
     * @param  Collection<int, HistoriaClinica>  $historias
     * @return Collection<int, array>
     */
    private function construirConceptosDesdeHistorias($historias)
    {
        return $historias->flatMap(function ($historiaClinica) {
            return $historiaClinica->muestras->map(function ($muestra) use ($historiaClinica) {
                return [
                    'historia_clinica_id' => $historiaClinica->id,
                    'numero_caso' => $historiaClinica->numero_caso,
                    'producto_id' => $muestra->producto_id,
                    'descripcion' => $muestra->producto->nombre ?? $muestra->servicio->descripcion ?? '',
                    'cantidad' => $muestra->cantidad,
                    'precio_unitario' => $muestra->producto->precio_unitario ?? 0,
                ];
            });
        })->values();
    }
}
