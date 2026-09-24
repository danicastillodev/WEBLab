<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\Explotacion;
use App\Models\FuncionZootecnica;
use App\Models\HistoriaClinica;
use App\Models\Muestra;
use App\Models\Municipio;
use App\Models\Pregunta;
use App\Models\Propietario;
use App\Models\Prueba;
use App\Models\Raza;
use App\Models\TipoMuestra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HistoriasClinicasController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $filtroKeys = ['fecha_desde', 'fecha_hasta', 'propietario', 'numero_caso', 'especie_id', 'ejercicio', 'estado', 'impreso'];

        // Primera visita sin parámetros → redirigir con la fecha de hoy y el
        // ejercicio en curso, que es el valor por defecto de ese filtro.
        if (! $request->hasAny($filtroKeys)) {
            $hoy = now()->toDateString();

            return redirect()->route('historias-clinicas.index', [
                'fecha_desde' => $hoy,
                'fecha_hasta' => $hoy,
                'ejercicio' => now()->year,
                'impreso' => HistoriaClinica::IMPRESO_NO,
            ]);
        }

        $fechaDesde = $request->input('fecha_desde', '');
        $fechaHasta = $request->input('fecha_hasta', '');
        $propietario = trim((string) $request->input('propietario', ''));
        $numeroCaso = $request->input('numero_caso', '');
        $especieId = $request->input('especie_id', '');
        $ejercicio = $request->input('ejercicio', '');
        $estado = $request->input('estado', '');
        $impreso = $request->input('impreso', '');

        // Estado e impreso vienen de un desplegable: un valor fuera del catálogo
        // se ignora en vez de filtrar por algo que no existe.
        $estado = in_array($estado, HistoriaClinica::ESTADOS, true) ? $estado : '';
        $impreso = in_array($impreso, HistoriaClinica::IMPRESOS, true) ? $impreso : '';

        $historias = HistoriaClinica::with(['propietario', 'creadoPor'])
            ->when(filled($fechaDesde), fn ($q) => $q->whereDate('fecha_recepcion', '>=', $fechaDesde))
            ->when(filled($fechaHasta), fn ($q) => $q->whereDate('fecha_recepcion', '<=', $fechaHasta))
            ->when(filled($propietario), fn ($q) => $q->whereHas('propietario', fn ($sq) => $sq->whereRaw("CONCAT(nombre, ' ', apellidos) LIKE ?", ["%{$propietario}%"])
            ))
            ->when(filled($numeroCaso), fn ($q) => $q->where('numero_caso', (int) $numeroCaso))
            ->when(filled($especieId), fn ($q) => $q->where('especie_id', (int) $especieId))
            ->when(filled($ejercicio), fn ($q) => $q->where('ejercicio', (int) $ejercicio))
            ->when(filled($estado), fn ($q) => $q->where('estado', $estado))
            ->when(filled($impreso), fn ($q) => $q->where('impreso', $impreso))
            ->orderByDesc('fecha_recepcion')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('HistoriasClinicas/Index', [
            'historias' => $historias,
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'propietario' => $propietario,
                'numero_caso' => $numeroCaso,
                'especie_id' => $especieId,
                'ejercicio' => $ejercicio,
                'estado' => $estado,
                'impreso' => $impreso,
            ],
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HistoriasClinicas/Create', [
            'propietarios' => Propietario::orderBy('apellidos')->orderBy('nombre')->get(['id', 'nombre', 'apellidos', 'telefono', 'curp', 'rfc']),
            'direcciones' => Direccion::with('municipio')->get(['id', 'propietario_id', 'calle', 'numero_exterior', 'numero_interior', 'colonia', 'municipio_id', 'codigo_postal', 'caseta', 'lote', 'parvada']),
            'explotaciones' => Explotacion::with('municipio')->get(['id', 'propietario_id', 'nombre', 'direccion', 'estado_id', 'municipio_id', 'caseta', 'lote', 'parvada']),
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'razas' => Raza::orderBy('nombre')->get(['id', 'nombre', 'especie_id']),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
            'pruebas' => Prueba::orderBy('nombre')->get(['id', 'nombre', 'especie_id']),
            'tipos_muestra' => TipoMuestra::orderBy('nombre')->get(['id', 'prueba_id', 'nombre']),
            'funciones_zootecnicas' => FuncionZootecnica::orderBy('nombre')->get(['id', 'nombre', 'especie_id']),
            'preguntas' => Pregunta::vigentes()->get(['id', 'texto', 'tipo']),
        ]);
    }

    public function storePropietario(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['nullable', 'string', 'size:18', 'unique:propietarios,curp'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'unique:propietarios,rfc'],
            'telefono' => ['nullable', 'digits:10'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un propietario con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        $propietario = Propietario::create([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => isset($validated['curp']) ? strtoupper($validated['curp']) : null,
            'rfc' => isset($validated['rfc']) ? strtoupper($validated['rfc']) : null,
            'telefono' => $validated['telefono'] ?? null,
        ]);

        return response()->json($propietario);
    }

    public function storeDireccion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'integer', 'exists:propietarios,id'],
            'calle' => ['required', 'string', 'max:200'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:150'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'caseta' => ['nullable', 'string', 'max:50'],
            'lote' => ['nullable', 'string', 'max:50'],
            'parvada' => ['nullable', 'string', 'max:50'],
        ]);

        $direccion = Direccion::create($validated);

        return response()->json($direccion->load('municipio'));
    }

    public function storeFuncionZootecnica(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('funcion_zootecnicas')->where('especie_id', $request->especie_id),
            ],
            'especie_id' => ['required', 'integer', 'exists:especies,id'],
        ]);

        $funcion = FuncionZootecnica::create($validated);

        return response()->json($funcion);
    }

    public function storePrueba(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'especie_id' => ['required', 'integer', 'exists:especies,id'],
        ]);

        $prueba = Prueba::create($validated);

        return response()->json($prueba);
    }

    public function storeTipoMuestra(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prueba_id' => ['required', 'integer', 'exists:pruebas,id'],
            'nombre' => ['required', 'string', 'max:150'],
        ]);

        $tipo = TipoMuestra::create($validated);

        return response()->json($tipo);
    }

    public function storeEspecie(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:especies'],
        ]);

        $especie = Especie::create($validated);

        return response()->json($especie);
    }

    public function storeRaza(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:razas'],
            'especie_id' => ['required', 'integer', 'exists:especies,id'],
        ]);

        $raza = Raza::create($validated);

        return response()->json($raza);
    }

    /*
    |--------------------------------------------------------------------------
    | Edición / eliminación en línea (modales del formulario de alta)
    |--------------------------------------------------------------------------
    */

    /**
     * Impide eliminar un registro que todavía está referenciado.
     * Responde 409 para que el modal muestre el motivo.
     */
    private function bloquearSiEnUso(bool $enUso, string $mensaje): void
    {
        abort_if($enUso, 409, $mensaje);
    }

    public function updatePropietario(Request $request, Propietario $propietario): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['nullable', 'string', 'size:18', Rule::unique('propietarios', 'curp')->ignore($propietario->id)],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', Rule::unique('propietarios', 'rfc')->ignore($propietario->id)],
            'telefono' => ['nullable', 'digits:10'],
        ], [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un propietario con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
        ]);

        $propietario->update([
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => isset($validated['curp']) ? strtoupper($validated['curp']) : null,
            'rfc' => isset($validated['rfc']) ? strtoupper($validated['rfc']) : null,
            'telefono' => $validated['telefono'] ?? null,
        ]);

        return response()->json($propietario);
    }

    public function destroyPropietario(Propietario $propietario): JsonResponse
    {
        // La FK es CASCADE: borrarlo arrastraría sus direcciones e historias clínicas.
        $this->bloquearSiEnUso(
            HistoriaClinica::where('propietario_id', $propietario->id)->exists(),
            'No se puede eliminar: el propietario tiene historias clínicas registradas.',
        );
        $this->bloquearSiEnUso(
            Direccion::where('propietario_id', $propietario->id)->exists(),
            'No se puede eliminar: el propietario tiene direcciones registradas.',
        );

        $propietario->delete();

        return response()->json(['id' => $propietario->id]);
    }

    public function updateDireccion(Request $request, Direccion $direccion): JsonResponse
    {
        $validated = $request->validate([
            'calle' => ['required', 'string', 'max:200'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:150'],
            'estado_id' => ['required', 'integer', 'exists:estados,id'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'codigo_postal' => ['required', 'string', 'max:10'],
            'caseta' => ['nullable', 'string', 'max:50'],
            'lote' => ['nullable', 'string', 'max:50'],
            'parvada' => ['nullable', 'string', 'max:50'],
        ]);

        $direccion->update($validated);

        return response()->json($direccion->load('municipio'));
    }

    public function destroyDireccion(Direccion $direccion): JsonResponse
    {
        $this->bloquearSiEnUso(
            HistoriaClinica::where('direccion_id', $direccion->id)->exists(),
            'No se puede eliminar: la dirección está usada en historias clínicas.',
        );

        $direccion->delete();

        return response()->json(['id' => $direccion->id]);
    }

    /* ── Explotación ── */

    public function storeExplotacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'integer', 'exists:propietarios,id'],
            'nombre' => ['required', 'string', 'max:200'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'municipio_id' => ['nullable', 'integer', 'exists:municipios,id'],
            'caseta' => ['nullable', 'string', 'max:50'],
            'lote' => ['nullable', 'string', 'max:50'],
            'parvada' => ['nullable', 'string', 'max:50'],
        ]);

        $explotacion = Explotacion::create($validated);

        return response()->json($explotacion->load('municipio'));
    }

    public function updateExplotacion(Request $request, Explotacion $explotacion): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'municipio_id' => ['nullable', 'integer', 'exists:municipios,id'],
            'caseta' => ['nullable', 'string', 'max:50'],
            'lote' => ['nullable', 'string', 'max:50'],
            'parvada' => ['nullable', 'string', 'max:50'],
        ]);

        $explotacion->update($validated);

        return response()->json($explotacion->load('municipio'));
    }

    public function destroyExplotacion(Explotacion $explotacion): JsonResponse
    {
        $this->bloquearSiEnUso(
            HistoriaClinica::where('explotacion_id', $explotacion->id)->exists(),
            'No se puede eliminar: la explotación está usada en historias clínicas.',
        );

        $explotacion->delete();

        return response()->json(['id' => $explotacion->id]);
    }

    public function updateEspecie(Request $request, Especie $especie): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('especies')->ignore($especie->id)],
        ]);

        $especie->update($validated);

        return response()->json($especie);
    }

    public function destroyEspecie(Especie $especie): JsonResponse
    {
        $this->bloquearSiEnUso(
            HistoriaClinica::where('especie_id', $especie->id)->exists(),
            'No se puede eliminar: la especie está usada en historias clínicas.',
        );
        // Razas, pruebas y funciones quedarían con especie_id nulo (SET NULL) e
        // invisibles en el formulario, así que también bloqueamos.
        $this->bloquearSiEnUso(
            Raza::where('especie_id', $especie->id)->exists()
                || Prueba::where('especie_id', $especie->id)->exists()
                || FuncionZootecnica::where('especie_id', $especie->id)->exists(),
            'No se puede eliminar: la especie tiene razas, análisis o funciones asociadas.',
        );

        $especie->delete();

        return response()->json(['id' => $especie->id]);
    }

    public function updateRaza(Request $request, Raza $raza): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('razas')->ignore($raza->id)],
        ]);

        $raza->update($validated);

        return response()->json($raza);
    }

    public function destroyRaza(Raza $raza): JsonResponse
    {
        $this->bloquearSiEnUso(
            HistoriaClinica::where('raza_id', $raza->id)->exists(),
            'No se puede eliminar: la raza está usada en historias clínicas.',
        );

        $raza->delete();

        return response()->json(['id' => $raza->id]);
    }

    public function updateFuncionZootecnica(Request $request, FuncionZootecnica $funcionZootecnica): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('funcion_zootecnicas')
                    ->where('especie_id', $funcionZootecnica->especie_id)
                    ->ignore($funcionZootecnica->id),
            ],
        ]);

        $funcionZootecnica->update($validated);

        return response()->json($funcionZootecnica);
    }

    public function destroyFuncionZootecnica(FuncionZootecnica $funcionZootecnica): JsonResponse
    {
        $this->bloquearSiEnUso(
            HistoriaClinica::where('funcion_zootecnica_id', $funcionZootecnica->id)->exists(),
            'No se puede eliminar: la función está usada en historias clínicas.',
        );

        $funcionZootecnica->delete();

        return response()->json(['id' => $funcionZootecnica->id]);
    }

    public function updatePrueba(Request $request, Prueba $prueba): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        $prueba->update($validated);

        return response()->json($prueba);
    }

    public function destroyPrueba(Prueba $prueba): JsonResponse
    {
        $this->bloquearSiEnUso(
            Muestra::where('prueba_id', $prueba->id)->exists(),
            'No se puede eliminar: el análisis está usado en muestras registradas.',
        );

        // Los tipos de muestra son hijos del análisis y se eliminan en cascada.
        $prueba->delete();

        return response()->json(['id' => $prueba->id]);
    }

    public function updateTipoMuestra(Request $request, TipoMuestra $tipoMuestra): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
        ]);

        $tipoMuestra->update($validated);

        return response()->json($tipoMuestra);
    }

    public function destroyTipoMuestra(TipoMuestra $tipoMuestra): JsonResponse
    {
        $this->bloquearSiEnUso(
            Muestra::where('tipo_muestra_id', $tipoMuestra->id)->exists(),
            'No se puede eliminar: el tipo de muestra está usado en muestras registradas.',
        );

        $tipoMuestra->delete();

        return response()->json(['id' => $tipoMuestra->id]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'integer', 'exists:propietarios,id'],
            'direccion_id' => ['required', 'integer', 'exists:direcciones,id'],
            'explotacion_id' => ['required', 'integer', 'exists:explotaciones,id'],
            'fecha_recepcion' => ['required', 'date'],
            'fecha_muestra' => ['required', 'date'],
            'especie_id' => ['required', 'integer', 'exists:especies,id'],
            'raza_id' => ['required', 'integer', 'exists:razas,id'],
            'funcion_zootecnica_id' => ['required', 'integer', 'exists:funcion_zootecnicas,id'],
            'sexo' => ['nullable', 'in:Hembra,Macho,Ambos,NA'],
            'edad_unidad' => ['required', 'in:Dias,Meses,Años,NR,NA'],
            'edad_valor' => ['nullable', 'integer', 'min:0'],
            'animales_explotacion' => ['nullable', 'integer', 'min:0'],
            'animales_muertos' => ['nullable', 'integer', 'min:0'],
            'animales_enfermos' => ['nullable', 'integer', 'min:0'],
            'notas_adicionales' => ['nullable', 'string', 'max:512'],
            'muestras' => ['required', 'array', 'min:1'],
            'muestras.*.prueba_id' => ['required', 'integer', 'exists:pruebas,id'],
            'muestras.*.cantidad' => ['required', 'integer', 'min:1'],
            'muestras.*.tipo_muestra_id' => ['nullable', 'integer', 'exists:tipo_muestras,id'],
            'muestras.*.notas' => ['nullable', 'string', 'max:1000'],
            'respuestas' => ['nullable', 'array'],
            'respuestas.*' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array($validated['edad_unidad'], ['NR', 'NA'])) {
            $validated['edad_valor'] = null;
        }

        $historia = DB::transaction(function () use ($validated) {
            // El ejercicio y el número de caso los asigna el modelo al crear.
            $historia = HistoriaClinica::create($validated);
            $historia->muestras()->createMany($validated['muestras']);

            return $historia;
        });

        $this->guardarRespuestas($historia, $validated['respuestas'] ?? []);

        return redirect()->route('historias-clinicas.index')
            ->with('success', 'Historia clínica creada.')
            ->with('numero_caso', $historia->numero_caso)
            ->with('historia_id', $historia->id);
    }

    public function show(HistoriaClinica $historiaClinica): Response
    {
        return Inertia::render('HistoriasClinicas/Show', [
            'historia' => $historiaClinica->load([
                'propietario',
                'direccion.municipio',
                'explotacion.municipio',
                'especie',
                'raza',
                'funcionZootecnica',
                'creadoPor',
                'muestras.prueba',
                'muestras.tipoMuestra',
                'respuestas.pregunta',
            ]),
        ]);
    }

    public function cancel(HistoriaClinica $historiaClinica): RedirectResponse
    {
        $historiaClinica->update(['estado' => HistoriaClinica::ESTADO_CANCELADA]);

        return redirect()->route('historias-clinicas.index')->with('success', 'Historia clínica cancelada.');
    }

    public function ticket(HistoriaClinica $historiaClinica): Response
    {
        return Inertia::render('HistoriasClinicas/Ticket', [
            'historia' => $historiaClinica->load([
                'propietario',
                'especie',
                'raza',
                'creadoPor',
                'muestras.prueba',
                'muestras.tipoMuestra',
            ]),
        ]);
    }

    /**
     * Guarda las respuestas del cuestionario. Las claves llegan desde el
     * formulario, así que se filtran contra las preguntas vigentes; las
     * respuestas vacías no se registran.
     *
     * @param  array<int|string, string|null>  $respuestas
     */
    private function guardarRespuestas(HistoriaClinica $historia, array $respuestas): void
    {
        if ($respuestas === []) {
            return;
        }

        $vigentes = Pregunta::vigentes()->pluck('id')->all();

        $filas = collect($respuestas)
            ->only($vigentes)
            ->reject(fn ($valor) => $valor === null || trim((string) $valor) === '')
            ->map(fn ($valor, $preguntaId) => [
                'pregunta_id' => (int) $preguntaId,
                'respuesta' => trim((string) $valor),
            ])
            ->values()
            ->all();

        if ($filas !== []) {
            $historia->respuestas()->createMany($filas);
        }
    }

    public function edit(HistoriaClinica $historiaClinica): Response
    {
        return Inertia::render('HistoriasClinicas/Edit', [
            'historia' => $historiaClinica->load(['propietario', 'direccion.municipio', 'especie', 'raza']),
            'propietarios' => Propietario::orderBy('apellidos')->orderBy('nombre')->get(['id', 'nombre', 'apellidos', 'telefono', 'curp', 'rfc']),
            'direcciones' => Direccion::with('municipio')->get(['id', 'propietario_id', 'calle', 'numero_exterior', 'numero_interior', 'colonia', 'municipio_id', 'codigo_postal', 'caseta', 'lote', 'parvada']),
            'especies' => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'razas' => Raza::orderBy('nombre')->get(['id', 'nombre', 'especie_id']),
        ]);
    }

    public function update(Request $request, HistoriaClinica $historiaClinica): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id' => ['required', 'integer', 'exists:propietarios,id'],
            'direccion_id' => ['required', 'integer', 'exists:direcciones,id'],
            'fecha_recepcion' => ['required', 'date'],
            'especie_id' => ['required', 'integer', 'exists:especies,id'],
            'raza_id' => ['required', 'integer', 'exists:razas,id'],
            'edad_unidad' => ['required', 'in:Dias,Meses,Años,NR,NA'],
            'edad_valor' => ['nullable', 'integer', 'min:0'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        if (in_array($validated['edad_unidad'], ['NR', 'NA'])) {
            $validated['edad_valor'] = null;
        }

        $historiaClinica->update($validated);

        return redirect()->route('historias-clinicas.index')->with('success', 'Historia clínica actualizada.');
    }

    public function destroy(HistoriaClinica $historiaClinica): RedirectResponse
    {
        $historiaClinica->delete();

        return redirect()->route('historias-clinicas.index')->with('success', 'Historia clínica eliminada.');
    }
}
