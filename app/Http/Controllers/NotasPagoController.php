<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\ParametroOperacion;
use App\Support\ImporteConLetra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'puede' => [
                'crear' => $user->tienePermisoGranular('facturacion.agregar_facturas'),
                'editar' => $user->tienePermisoGranular('facturacion.modificar_facturas'),
                'cancelar' => $user->tienePermisoGranular('facturacion.cancelar_factura'),
                // Borrado definitivo, temporal y sólo para administradores:
                // la vía normal es cancelar.
                'eliminar' => (bool) $user->es_admin,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.agregar_facturas'), 403);

        return Inertia::render('NotasPago/Create', [
            ...$this->propsFormulario(),
            'historias_preseleccionadas' => $request->boolean('preseleccionar_hoy')
                ? $this->historiasCapturadasHoySinNotaPago($request)
                : null,
        ]);
    }

    public function edit(Request $request, NotaPago $notaPago): Response|RedirectResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.modificar_facturas'), 403);

        if ($notaPago->cancelada) {
            return redirect()->route('notas-pago.index')
                ->with('error', "La nota de pago folio {$notaPago->folio} está cancelada y no se puede editar.");
        }

        $historias = $notaPago->historiasClinicas()->with($this->relacionesConceptos())->orderBy('numero_caso')->get();
        $guardados = $notaPago->conceptos()->get();

        // Los precios se recalculan con el catálogo vigente; se conservan el
        // producto elegido y la cantidad que esta nota factura de cada
        // muestra. Las muestras que la nota dejó fuera (porque se facturan en
        // otra) aparecen en 0.
        $conceptos = $this->construirConceptosDesdeHistorias($historias, $notaPago->id)->map(function ($c) use ($guardados) {
            $guardado = $guardados->firstWhere('muestra_id', $c['muestra_id']);
            $c = $this->aplicarProductoElegido($c, $guardado?->producto_id) ?? $c;

            return [...$c, 'cantidad' => $guardado ? (int) $guardado->cantidad : 0];
        });

        return Inertia::render('NotasPago/Create', [
            ...$this->propsFormulario(),
            'nota' => [
                ...$notaPago->only(['id', 'folio', 'cliente_id', 'concepto', 'tipo_pago', 'pagada', 'notas_adicionales']),
                'historias' => $historias->map(fn ($h) => ['id' => $h->id, 'numero_caso' => $h->numero_caso])->values(),
                'conceptos' => $conceptos,
            ],
        ]);
    }

    public function update(Request $request, NotaPago $notaPago): RedirectResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.modificar_facturas'), 403);

        if ($notaPago->cancelada) {
            return redirect()->route('notas-pago.index')
                ->with('error', "La nota de pago folio {$notaPago->folio} está cancelada y no se puede editar.");
        }

        $validated = $this->validarNota($request);
        $historias = $this->historiasDeLaNota($validated['historia_clinica_ids'], $notaPago);
        if ($historias instanceof RedirectResponse) {
            return $historias;
        }

        $conceptos = $this->conceptosParaGuardar($historias, $validated['cantidades'] ?? [], $validated['productos'] ?? [], $notaPago);

        DB::transaction(function () use ($notaPago, $validated, $conceptos) {
            $notaPago->update([
                ...$this->datosGenerales($validated),
                ...$this->totales($conceptos),
            ]);

            $notaPago->conceptos()->delete();
            $notaPago->conceptos()->createMany($conceptos->all());
            $notaPago->historiasClinicas()->sync($conceptos->pluck('historia_clinica_id')->unique()->values());
        });

        return redirect()->route('notas-pago.index')
            ->with('success', "Nota de pago folio {$notaPago->folio} actualizada.");
    }

    /**
     * Como en el sistema anterior, cancelar no borra la nota: conserva su
     * folio y queda el motivo. Sus historias clínicas quedan libres para
     * facturarse en otra nota (ver HistoriaClinica::notasPagoVigentes).
     */
    public function cancelar(Request $request, NotaPago $notaPago): RedirectResponse
    {
        abort_unless($request->user()->tienePermisoGranular('facturacion.cancelar_factura'), 403);

        $validated = $request->validate([
            'motivo_cancelacion' => ['required', 'string', 'max:1000'],
        ], [
            'motivo_cancelacion.required' => 'Escribe el motivo de la cancelación.',
        ]);

        if ($notaPago->cancelada) {
            return redirect()->route('notas-pago.index')
                ->with('error', "La nota de pago folio {$notaPago->folio} ya estaba cancelada.");
        }

        $notaPago->update([
            'cancelada' => true,
            'motivo_cancelacion' => $validated['motivo_cancelacion'],
            'cancelada_at' => now(),
            'cancelada_por' => $request->user()->id,
        ]);

        return redirect()->route('notas-pago.index')
            ->with('success', "Nota de pago folio {$notaPago->folio} cancelada.");
    }

    public function destroy(Request $request, NotaPago $notaPago): RedirectResponse
    {
        abort_unless($request->user()->es_admin, 403);

        // Conceptos y vínculos con historias clínicas se borran en cascada.
        $notaPago->delete();

        return redirect()->route('notas-pago.index')
            ->with('success', "Nota de pago folio {$notaPago->folio} eliminada.");
    }

    /** @return array<string, mixed> */
    private function propsFormulario(): array
    {
        return [
            'clientes' => Cliente::with(['estado:id,nombre', 'municipio:id,nombre'])->orderBy('nombre')->get(),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
            'conceptos_opciones' => NotaPago::CONCEPTOS,
            'tipos_pago' => NotaPago::TIPOS_PAGO,
            'iva_porcentaje' => ParametroOperacion::first()?->iva_porcentaje ?? 16,
        ];
    }

    /**
     * Historias clínicas capturadas el día de hoy por el usuario actual a las
     * que todavía les queda algo por facturar — usado para preseleccionarlas
     * al llegar desde "¿Desea crear una nota de pago?" en el alta de una
     * historia clínica.
     */
    private function historiasCapturadasHoySinNotaPago(Request $request): ?array
    {
        $historias = HistoriaClinica::with($this->relacionesConceptos())
            ->where('created_by', $request->user()->id)
            ->whereDate('created_at', today())
            ->conPendientePorFacturar()
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

        $validated = $this->validarNota($request);
        $historias = $this->historiasDeLaNota($validated['historia_clinica_ids']);
        if ($historias instanceof RedirectResponse) {
            return $historias;
        }

        $conceptos = $this->conceptosParaGuardar($historias, $validated['cantidades'] ?? [], $validated['productos'] ?? []);

        $notaPago = DB::transaction(function () use ($validated, $conceptos) {
            $notaPago = NotaPago::create([
                'folio' => NotaPago::siguienteFolio(),
                ...$this->datosGenerales($validated),
                ...$this->totales($conceptos),
            ]);

            $notaPago->conceptos()->createMany($conceptos->all());
            // Sólo se ligan las historias de las que la nota factura algo.
            $notaPago->historiasClinicas()->attach($conceptos->pluck('historia_clinica_id')->unique()->values());

            return $notaPago;
        });

        return redirect()->route('notas-pago.index')
            ->with('nota_pago_folio', $notaPago->folio)
            ->with('nota_pago_id', $notaPago->id);
    }

    /** @return array<string, mixed> */
    private function validarNota(Request $request): array
    {
        return $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'historia_clinica_ids' => ['required', 'array', 'min:1'],
            'historia_clinica_ids.*' => ['integer', 'exists:historias_clinicas,id'],
            // Cantidad que esta nota factura de cada muestra, indexada por
            // muestra_id. 0 deja la muestra fuera para facturarla en otra nota.
            'cantidades' => ['nullable', 'array'],
            'cantidades.*' => ['required', 'integer', 'min:0', 'max:99999'],
            // Producto elegido para cada concepto, indexado por muestra_id: sólo
            // se aceptan los relacionados con el análisis de la muestra.
            'productos' => ['nullable', 'array'],
            'productos.*' => ['nullable', 'integer'],
            'concepto' => ['required', 'string', 'in:'.implode(',', NotaPago::CONCEPTOS)],
            'tipo_pago' => ['required', 'string', 'in:'.implode(',', NotaPago::TIPOS_PAGO)],
            'pagada' => ['boolean'],
            'notas_adicionales' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Historias clínicas a facturar, ya con lo necesario para armar sus
     * conceptos. Rechaza las que ya se facturaron por completo en otras notas
     * vigentes; al editar, lo que factura la propia nota no cuenta.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, HistoriaClinica>|RedirectResponse
     */
    private function historiasDeLaNota(array $ids, ?NotaPago $notaActual = null): Collection|RedirectResponse
    {
        $historias = HistoriaClinica::with($this->relacionesConceptos())->whereIn('id', $ids)->get();

        if ($historias->count() !== count(array_unique($ids))) {
            abort(404, 'Una o más historias clínicas seleccionadas no existen o están repetidas.');
        }

        $conPendiente = HistoriaClinica::conPendientePorFacturar($notaActual?->id)->whereIn('id', $ids)->pluck('id');
        $completa = $historias->first(fn ($h) => ! $conPendiente->contains($h->id));

        if ($completa) {
            return back()->withErrors([
                'historia_clinica_ids' => "La historia clínica (caso {$completa->numero_caso}) ya está facturada por completo en otra nota de pago.",
            ])->withInput();
        }

        return $historias;
    }

    /**
     * Los conceptos nunca se confían al cliente: se reconstruyen a partir de
     * las muestras de las historias, igual que en conceptosDeHistorias(),
     * para que no sea posible manipular productos o precios desde el
     * navegador. Lo único que el usuario elige es cuánto factura esta nota de
     * cada muestra. 0 la deja fuera para facturarla en otra nota. No se topa
     * en lo que queda por facturar: hay productos que se cobran por otra
     * unidad (p. ej. kilos de RPBI) distinta al número de muestras.
     *
     * @param  Collection<int, HistoriaClinica>  $historias
     * @param  array<int|string, int|string>  $cantidades  indexadas por muestra_id
     * @return Collection<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    private function conceptosParaGuardar(Collection $historias, array $cantidades, array $productos = [], ?NotaPago $notaActual = null): Collection
    {
        $errores = [];

        $conceptos = $this->construirConceptosDesdeHistorias($historias, $notaActual?->id)->map(function ($c) use ($cantidades, $productos, &$errores) {
            $cantidad = (int) ($cantidades[$c['muestra_id']] ?? $c['cantidad']);
            $concepto = $this->aplicarProductoElegido($c, $productos[$c['muestra_id']] ?? null);

            if ($concepto === null) {
                $errores["productos.{$c['muestra_id']}"] = 'El producto elegido no corresponde al análisis de la muestra.';
                $concepto = $c;
            }

            return [
                'muestra_id' => $concepto['muestra_id'],
                'producto_id' => $concepto['producto_id'],
                'historia_clinica_id' => $concepto['historia_clinica_id'],
                'descripcion' => $concepto['descripcion'],
                'cantidad' => $cantidad,
                'precio_unitario' => $concepto['precio_unitario'],
                'importe' => round($cantidad * $concepto['precio_unitario'], 2),
            ];
        })->filter(fn ($c) => $c['cantidad'] > 0)->values();

        if ($errores === [] && $conceptos->isEmpty()) {
            $errores['cantidades'] = 'La nota de pago debe facturar al menos un concepto.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        return $conceptos;
    }

    /**
     * Cambia el producto de un concepto por otro de los relacionados con el
     * análisis de su muestra; nombre y precio salen del catálogo, nunca del
     * navegador. Devuelve null si el producto no es de esa muestra.
     *
     * @param  array<string, mixed>  $concepto  como lo arma construirConceptosDesdeHistorias()
     * @return array<string, mixed>|null
     */
    private function aplicarProductoElegido(array $concepto, int|string|null $productoId): ?array
    {
        if ($productoId === null || $productoId === '' || (int) $productoId === $concepto['producto_id']) {
            return $concepto;
        }

        $producto = $concepto['productos']->firstWhere('id', (int) $productoId);

        return $producto ? [
            ...$concepto,
            'producto_id' => $producto['id'],
            'descripcion' => $producto['nombre'],
            'precio_unitario' => $producto['precio_unitario'],
        ] : null;
    }

    /**
     * Unidades ya facturadas de cada muestra en notas de pago vigentes.
     *
     * @param  Collection<int, int>  $muestraIds
     * @return Collection<int, int> indexada por muestra_id
     */
    private function cantidadesFacturadas(Collection $muestraIds, ?int $exceptoNotaPagoId = null): Collection
    {
        if ($muestraIds->isEmpty()) {
            return collect();
        }

        return DB::table('nota_pago_conceptos as npc')
            ->join('notas_pago as np', 'np.id', '=', 'npc.nota_pago_id')
            ->where('np.cancelada', false)
            ->when($exceptoNotaPagoId, fn ($q) => $q->where('np.id', '!=', $exceptoNotaPagoId))
            ->whereIn('npc.muestra_id', $muestraIds)
            ->groupBy('npc.muestra_id')
            ->select('npc.muestra_id', DB::raw('sum(npc.cantidad) as total'))
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->muestra_id => (int) $fila->total]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datosGenerales(array $validated): array
    {
        return [
            'cliente_id' => $validated['cliente_id'],
            'concepto' => $validated['concepto'],
            'tipo_pago' => $validated['tipo_pago'],
            'pagada' => $validated['pagada'] ?? false,
            'notas_adicionales' => $validated['notas_adicionales'] ?? null,
        ];
    }

    /**
     * El IVA nunca se confía al cliente: se toma el porcentaje vigente en
     * Configuración General → Parámetros de Operación en este instante (16%
     * es el mismo valor por defecto que usa ParametrosOperacionController
     * cuando todavía no existe el registro de parámetros).
     *
     * @param  Collection<int, array<string, mixed>>  $conceptos
     * @return array<string, float>
     */
    private function totales(Collection $conceptos): array
    {
        $ivaPorcentaje = (float) (ParametroOperacion::first()?->iva_porcentaje ?? 16);
        $subtotal = round((float) $conceptos->sum('importe'), 2);
        $ivaMonto = round($subtotal * $ivaPorcentaje / 100, 2);

        return [
            'subtotal' => $subtotal,
            'iva_porcentaje' => $ivaPorcentaje,
            'iva_monto' => $ivaMonto,
            'total' => round($subtotal + $ivaMonto, 2),
        ];
    }

    /**
     * Formato de impresión de la nota: datos del cliente y conceptos arriba,
     * subtotal, IVA y total (con número y con letra) al pie de la hoja.
     */
    public function imprimir(Request $request, NotaPago $notaPago): Response
    {
        $user = $request->user();
        abort_unless(collect(self::PERMISOS_LISTADO)->contains(fn ($clave) => $user->tienePermisoGranular($clave)), 403);

        $notaPago->load([
            'cliente.estado:id,nombre',
            'cliente.municipio:id,nombre',
            'conceptos.historiaClinica:id,numero_caso',
            'historiasClinicas:id,numero_caso',
        ]);

        // Las notas creadas antes de guardar la historia de cada concepto no
        // saben de qué caso salió cada uno; si la nota tiene un solo caso, es
        // ese.
        $casoUnico = $notaPago->historiasClinicas->count() === 1
            ? $notaPago->historiasClinicas->first()->numero_caso
            : null;

        $cliente = $notaPago->cliente;

        return Inertia::render('NotasPago/Imprimir', [
            'nota' => [
                'id' => $notaPago->id,
                'folio' => $notaPago->folio,
                // Se formatea aquí para usar la zona horaria de la aplicación
                // y no la UTC con la que se serializa created_at.
                'fecha' => $notaPago->created_at?->format('d/m/Y'),
                'tipo_pago' => $notaPago->tipo_pago,
                'pagada' => $notaPago->pagada,
                'subtotal' => $notaPago->subtotal,
                'iva_monto' => $notaPago->iva_monto,
                'total' => $notaPago->total,
                'total_letra' => ImporteConLetra::pesos($notaPago->total),
                'cliente' => $cliente ? [
                    'nombre' => $cliente->nombre,
                    'rfc' => $cliente->rfc,
                    'domicilio' => trim(collect([
                        $cliente->calle,
                        $cliente->numero_exterior ? "# {$cliente->numero_exterior}" : null,
                        $cliente->numero_interior ? "INT. {$cliente->numero_interior}" : null,
                    ])->filter()->implode(' ')),
                    'colonia' => $cliente->colonia,
                    'codigo_postal' => $cliente->codigo_postal,
                    'poblacion' => collect([$cliente->municipio?->nombre, $cliente->estado?->nombre])->filter()->implode(', '),
                ] : null,
                'conceptos' => $notaPago->conceptos->map(fn ($c) => [
                    'id' => $c->id,
                    'descripcion' => $c->descripcion,
                    'numero_caso' => $c->historiaClinica?->numero_caso ?? $casoUnico,
                    'cantidad' => (float) $c->cantidad,
                    'precio_unitario' => $c->precio_unitario,
                    'importe' => $c->importe,
                ])->values(),
            ],
        ]);
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

        // Al editar, lo que factura la propia nota no cuenta: si el usuario
        // quita una historia, debe poder volver a agregarla.
        $notaActualId = $request->integer('nota_pago_id') ?: null;

        $historias = HistoriaClinica::with('propietario')
            // Sólo se ofrecen historias con algo por facturar: una historia
            // puede repartirse en varias notas hasta completar sus muestras.
            ->conPendientePorFacturar($notaActualId)
            ->withExists(['notasPagoVigentes as facturada_parcialmente' => fn ($q) => $q->when(
                $notaActualId,
                fn ($q) => $q->where('notas_pago.id', '!=', $notaActualId),
            )])
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
                'facturada_parcialmente' => (bool) $h->facturada_parcialmente,
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
            'conceptos' => $this->construirConceptosDesdeHistorias($historias, $request->integer('nota_pago_id') ?: null),
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
     * Una muestra puede facturarse en varias notas: por_facturar es lo que le
     * queda después de las notas vigentes (sin contar $exceptoNotaPagoId al
     * editar), y es también la cantidad propuesta. Las muestras ya
     * facturadas por completo no generan concepto.
     *
     * @param  Collection<int, HistoriaClinica>  $historias
     * @return Collection<int, array>
     */
    private function construirConceptosDesdeHistorias($historias, ?int $exceptoNotaPagoId = null)
    {
        $facturado = $this->cantidadesFacturadas(
            $historias->flatMap(fn ($h) => $h->muestras->pluck('id'))->values(),
            $exceptoNotaPagoId,
        );

        return $historias->flatMap(function ($historiaClinica) use ($facturado) {
            return $historiaClinica->muestras->map(function ($muestra) use ($historiaClinica, $facturado) {
                $producto = $muestra->producto ?? $muestra->servicio?->productos->first();
                $porFacturar = max(0, (int) $muestra->cantidad - ($facturado[$muestra->id] ?? 0));

                return [
                    'muestra_id' => $muestra->id,
                    'historia_clinica_id' => $historiaClinica->id,
                    'numero_caso' => $historiaClinica->numero_caso,
                    'producto_id' => $producto?->id,
                    'descripcion' => $producto->nombre ?? $muestra->servicio->descripcion ?? '',
                    'cantidad' => $porFacturar,
                    'cantidad_muestra' => (int) $muestra->cantidad,
                    'por_facturar' => $porFacturar,
                    'precio_unitario' => $producto->precio_unitario ?? 0,
                    'productos' => $this->productosDeLaMuestra($muestra),
                ];
            })->filter(fn ($c) => $c['por_facturar'] > 0);
        })->values();
    }

    /**
     * Productos entre los que se puede elegir para facturar una muestra: los
     * activos del análisis (servicio) capturado. Si la muestra trae un
     * producto propio de cuando se elegía en la historia, también se ofrece.
     *
     * @return Collection<int, array{id: int, nombre: string, precio_unitario: string}>
     */
    private function productosDeLaMuestra($muestra): Collection
    {
        $productos = $muestra->servicio?->productos ?? collect();

        if ($muestra->producto && ! $productos->contains('id', $muestra->producto->id)) {
            $productos = $productos->prepend($muestra->producto);
        }

        return $productos->map(fn ($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'precio_unitario' => $p->precio_unitario,
        ])->values();
    }
}
