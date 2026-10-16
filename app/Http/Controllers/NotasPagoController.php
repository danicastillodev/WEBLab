<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\ParametroOperacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NotasPagoController extends Controller
{
    /**
     * Permisos granulares que dan acceso al listado: no hay uno específico
     * de "consultar", así que basta con cualquiera que opere sobre notas.
     */
    private const PERMISOS_LISTADO = [
        'facturacion.agregar_facturas',
        'facturacion.modificar_facturas',
        'facturacion.cancelar_factura',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(collect(self::PERMISOS_LISTADO)->contains(fn ($clave) => $user->tienePermisoGranular($clave)), 403);

        $filtros = [
            'buscar' => trim((string) $request->input('buscar', '')),
            'fecha_desde' => (string) $request->input('fecha_desde', ''),
            'fecha_hasta' => (string) $request->input('fecha_hasta', ''),
            'pagada' => (string) $request->input('pagada', ''),
        ];

        // El DatePickerInput envía las fechas como aaaa/mm/dd.
        $fechaDesde = str_replace('/', '-', $filtros['fecha_desde']);
        $fechaHasta = str_replace('/', '-', $filtros['fecha_hasta']);

        $notas = NotaPago::with(['cliente:id,nombre,rfc', 'historiasClinicas:id,numero_caso'])
            ->when($filtros['buscar'] !== '', function ($query) use ($filtros) {
                $buscar = $filtros['buscar'];
                $query->where(fn ($q) => $q
                    ->where('folio', $buscar)
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$buscar}%")->orWhere('rfc', 'like', "%{$buscar}%"))
                    ->orWhereHas('historiasClinicas', fn ($h) => $h->where('numero_caso', $buscar)));
            })
            ->when($fechaDesde !== '', fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
            ->when($fechaHasta !== '', fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
            ->when(in_array($filtros['pagada'], ['0', '1'], true), fn ($q) => $q->where('pagada', $filtros['pagada'] === '1'))
            ->orderByDesc('folio')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('NotasPago/Index', [
            'notas' => $notas,
            'filtros' => $filtros,
            'puede' => ['crear' => $user->tienePermisoGranular('facturacion.agregar_facturas')],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        return Inertia::render('NotasPago/Create', [
            'clientes' => Cliente::with(['estado:id,nombre', 'municipio:id,nombre'])->orderBy('nombre')->get(),
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
        $historias = HistoriaClinica::with($this->relacionesConceptos())
            ->where('created_by', $request->user()->id)
            ->whereDate('created_at', today())
            ->whereDoesntHave('notasPago')
            ->orderBy('numero_caso')
            ->get();

        if ($historias->isEmpty()) {
            return null;
        }

        return [
            'historias' => $historias->map(fn ($h) => ['id' => $h->id, 'numero_caso' => $h->numero_caso])->values(),
            'conceptos' => $this->construirConceptosDesdeHistorias($historias),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'historia_clinica_ids' => ['required', 'array', 'min:1'],
            'historia_clinica_ids.*' => ['integer', 'exists:historias_clinicas,id'],
            // Cantidad ajustada por el usuario en cada concepto, indexada por muestra_id.
            'cantidades' => ['nullable', 'array'],
            'cantidades.*' => ['required', 'integer', 'min:1', 'max:99999'],
            'concepto' => ['required', 'string', 'in:'.implode(',', NotaPago::CONCEPTOS)],
            'tipo_pago' => ['required', 'string', 'in:'.implode(',', NotaPago::TIPOS_PAGO)],
            'pagada' => ['boolean'],
            'notas_adicionales' => ['nullable', 'string', 'max:1000'],
        ]);

        // Los conceptos nunca se confían al cliente: se reconstruyen aquí a
        // partir de las muestras de las historias clínicas seleccionadas,
        // igual que en conceptosDeHistorias(), para que no sea posible
        // manipular productos o precios desde el navegador. Lo único que el
        // usuario puede ajustar es la cantidad de cada concepto.
        $historias = HistoriaClinica::with($this->relacionesConceptos())
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

        $cantidades = $validated['cantidades'] ?? [];

        $notaPago = DB::transaction(function () use ($validated, $historias, $ivaPorcentaje, $cantidades) {
            $conceptos = $this->construirConceptosDesdeHistorias($historias)->map(function ($c) use ($cantidades) {
                $cantidad = (int) ($cantidades[$c['muestra_id']] ?? $c['cantidad']);

                return [
                    'producto_id' => $c['producto_id'],
                    'descripcion' => $c['descripcion'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $c['precio_unitario'],
                    'importe' => round($cantidad * $c['precio_unitario'], 2),
                ];
            });

            $subtotal = round((float) $conceptos->sum('importe'), 2);
            $ivaMonto = round($subtotal * $ivaPorcentaje / 100, 2);

            $notaPago = NotaPago::create([
                'folio' => NotaPago::siguienteFolio(),
                'cliente_id' => $validated['cliente_id'],
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

        return redirect()->route('notas-pago.index')
            ->with('success', "Nota de pago folio {$notaPago->folio} creada.");
    }

    /**
     * Alta rápida de un cliente desde el modal del formulario (vía axios).
     */
    public function storeCliente(ClienteRequest $request): JsonResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $cliente = Cliente::create($request->validated());

        return response()->json($cliente->load(['estado:id,nombre', 'municipio:id,nombre']), 201);
    }

    public function updateCliente(ClienteRequest $request, Cliente $cliente): JsonResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        $cliente->update($request->validated());

        return response()->json($cliente->load(['estado:id,nombre', 'municipio:id,nombre']));
    }

    public function destroyCliente(Request $request, Cliente $cliente): JsonResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        if ($cliente->notasPago()->exists()) {
            return response()->json(['message' => 'No se puede eliminar: el cliente ya tiene notas de pago.'], 422);
        }

        $cliente->delete();

        return response()->json(null, 204);
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

        $historias = HistoriaClinica::with($this->relacionesConceptos())
            ->whereIn('id', $validated['historia_clinica_ids'])
            ->orderBy('numero_caso')
            ->get();

        if ($historias->isEmpty()) {
            return response()->json(['message' => 'No se encontraron las historias clínicas seleccionadas.'], 404);
        }

        return response()->json([
            'historias' => $historias->map(fn ($h) => ['id' => $h->id, 'numero_caso' => $h->numero_caso])->values(),
            'conceptos' => $this->construirConceptosDesdeHistorias($historias),
        ]);
    }

    /**
     * Relaciones que necesita construirConceptosDesdeHistorias(): de cada
     * servicio, solo sus productos activos y en orden de alta.
     *
     * @return array<int|string, mixed>
     */
    private function relacionesConceptos(): array
    {
        return [
            'muestras.producto',
            'muestras.servicio.productos' => fn ($q) => $q->where('activo', true)->orderBy('id'),
        ];
    }

    /**
     * Conceptos (uno por muestra) de un conjunto de historias clínicas. El
     * precio sale del producto asociado al servicio de cada muestra; si el
     * servicio tiene varios productos activos se toma el primero dado de alta.
     * Las muestras capturadas antes, cuando el producto se elegía en la
     * historia, conservan ese producto. Sin producto, el precio es 0.
     *
     * @param  Collection<int, HistoriaClinica>  $historias
     * @return Collection<int, array>
     */
    private function construirConceptosDesdeHistorias($historias)
    {
        return $historias->flatMap(function ($historiaClinica) {
            return $historiaClinica->muestras->map(function ($muestra) use ($historiaClinica) {
                $producto = $muestra->producto ?? $muestra->servicio?->productos->first();

                return [
                    'muestra_id' => $muestra->id,
                    'historia_clinica_id' => $historiaClinica->id,
                    'numero_caso' => $historiaClinica->numero_caso,
                    'producto_id' => $producto?->id,
                    'descripcion' => $producto->nombre ?? $muestra->servicio->descripcion ?? '',
                    'cantidad' => $muestra->cantidad,
                    'precio_unitario' => $producto->precio_unitario ?? 0,
                ];
            });
        })->values();
    }
}
