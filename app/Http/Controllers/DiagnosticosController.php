<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Diagnostico;
use App\Models\HistoriaClinica;
use App\Models\Muestra;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticosController extends Controller
{
    /**
     * Permisos granulares que dan acceso al listado: no hay uno específico
     * de "consultar", así que basta con cualquiera del grupo Diagnósticos.
     */
    private const PERMISOS_LISTADO = [
        'diagnosticos.agregar',
        'diagnosticos.modificar',
        'diagnosticos.emitir',
        'diagnosticos.consultar_de_otros',
        'diagnosticos.modificar_emitidos',
        'diagnosticos.cancelar',
    ];

    /** Etiquetas de formato que se conservan en el resultado; todo lo demás se quita. */
    private const ETIQUETAS_RESULTADO = '<b><strong><i><em><u><ul><ol><li><p><br><div>';

    /**
     * Listado de los análisis (muestras) de las historias clínicas cuyo
     * análisis pertenece a alguna de las áreas asignadas al usuario. Los
     * administradores ven todas las áreas. Sin filtros, se muestran sólo los
     * análisis pendientes (sin diagnóstico).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->puedeEntrar($user), 403);

        $areas = $this->areasDe($user);
        $servicios = Servicio::whereIn('area_id', $areas->pluck('id'))
            ->orderBy('descripcion')
            ->get(['id', 'siglas', 'descripcion']);

        $filtros = [
            'estado' => (string) $request->input('estado', Diagnostico::ESTADO_PENDIENTE),
            'numero_caso' => (string) $request->input('numero_caso', ''),
            'servicio_id' => (string) $request->input('servicio_id', ''),
            'fecha_desde' => (string) $request->input('fecha_desde', ''),
            'fecha_hasta' => (string) $request->input('fecha_hasta', ''),
        ];

        // El DatePickerInput envía las fechas como aaaa/mm/dd; una fecha que
        // no tenga ese formato se ignora.
        $fechaDesde = $this->fechaFiltro($filtros['fecha_desde']);
        $fechaHasta = $this->fechaFiltro($filtros['fecha_hasta']);

        // Estado y análisis vienen de un desplegable: un valor fuera de lo
        // permitido se ignora en vez de filtrar por algo que no existe (o de
        // dejar ver un análisis de un área que no es del usuario).
        if (! in_array($filtros['estado'], Diagnostico::ESTADOS, true)) {
            $filtros['estado'] = '';
        }
        if (! $servicios->contains('id', (int) $filtros['servicio_id'])) {
            $filtros['servicio_id'] = '';
        }

        $analisis = Muestra::query()
            ->with([
                'historiaClinica:id,numero_caso,fecha_recepcion',
                'servicio:id,siglas,descripcion',
                'diagnostico:id,muestra_id,estado,fecha_realizacion,fecha_emision,created_by',
                'diagnostico.creadoPor:id,name',
            ])
            ->whereIn('servicio_id', $servicios->pluck('id'))
            ->when($filtros['servicio_id'] !== '', fn ($q) => $q->where('servicio_id', (int) $filtros['servicio_id']))
            // Los análisis de una historia cancelada ya no se trabajan.
            ->whereHas('historiaClinica', fn (Builder $h) => $h
                ->where('estado', '!=', HistoriaClinica::ESTADO_CANCELADA)
                ->when(filled($filtros['numero_caso']), fn ($q) => $q->where('numero_caso', (int) $filtros['numero_caso']))
                ->when($fechaDesde, fn ($q) => $q->whereDate('fecha_recepcion', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('fecha_recepcion', '<=', $fechaHasta)))
            ->when($filtros['estado'] === Diagnostico::ESTADO_PENDIENTE, fn ($q) => $q->whereDoesntHave('diagnostico'))
            ->when(! in_array($filtros['estado'], ['', Diagnostico::ESTADO_PENDIENTE], true), fn ($q) => $q
                ->whereHas('diagnostico', fn ($d) => $d->where('estado', $filtros['estado'])))
            ->orderByDesc(
                HistoriaClinica::select('fecha_recepcion')->whereColumn('historias_clinicas.id', 'muestras.historia_clinica_id')
            )
            ->orderBy('muestras.id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Diagnosticos/Index', [
            'analisis' => $analisis,
            'filtros' => $filtros,
            'tieneAreas' => $areas->isNotEmpty(),
            'servicios' => $servicios,
        ]);
    }

    /**
     * Pantalla de captura del diagnóstico de un análisis: los datos de la
     * historia clínica de sólo lectura y, debajo, el diagnóstico (nuevo o el
     * que ya tenga).
     */
    public function edit(Request $request, Muestra $muestra): Response
    {
        $user = $request->user();
        $this->autorizarAnalisis($user, $muestra);

        $muestra->load([
            'historiaClinica:id,numero_caso,fecha_recepcion,fecha_muestra,especie_id',
            'historiaClinica.especie:id,nombre',
            'servicio:id,siglas,descripcion',
            'tipoMuestra:id,nombre',
            'diagnostico.muestras',
            'diagnostico.creadoPor:id,name',
        ]);

        $diagnostico = $muestra->diagnostico;

        return Inertia::render('Diagnosticos/Captura', [
            'analisis' => $muestra,
            'diagnostico' => $diagnostico,
            'resultados' => Diagnostico::RESULTADOS,
            'maximoResultado' => Diagnostico::MAXIMO_RESULTADO,
            'permisos' => [
                'guardar' => $this->puedeGuardar($user, $diagnostico),
                'emitir' => $this->puedeEmitir($user, $diagnostico),
                'cancelar' => $this->puedeCancelar($user, $diagnostico),
            ],
        ]);
    }

    /**
     * Guarda el diagnóstico. Con accion=guardar queda en proceso; con
     * accion=emitir se guarda y se emite.
     */
    public function guardar(Request $request, Muestra $muestra): RedirectResponse
    {
        $user = $request->user();
        $this->autorizarAnalisis($user, $muestra);

        $diagnostico = $muestra->diagnostico;
        $emitir = $request->input('accion') === 'emitir';

        abort_unless($emitir ? $this->puedeEmitir($user, $diagnostico) : $this->puedeGuardar($user, $diagnostico), 403);

        $validated = $this->validarDiagnostico($request, $muestra, $emitir);

        DB::transaction(function () use ($muestra, $diagnostico, $validated, $emitir, $user) {
            $diagnostico ??= new Diagnostico(['muestra_id' => $muestra->id, 'created_by' => $user->id]);

            $diagnostico->fill([
                'fecha_realizacion' => $validated['fecha_realizacion'],
                'resultado' => $validated['resultado'],
                'estado' => $emitir ? Diagnostico::ESTADO_EMITIDO : Diagnostico::ESTADO_EN_PROCESO,
                'fecha_emision' => $emitir ? now() : null,
            ])->save();

            $diagnostico->muestras()->delete();
            $diagnostico->muestras()->createMany($validated['muestras']);
        });

        return redirect()
            ->route('diagnosticos.edit', $muestra)
            ->with('success', $emitir ? 'Diagnóstico emitido.' : 'Diagnóstico guardado.');
    }

    /**
     * Cancela el análisis. Se puede cancelar aunque todavía no tenga
     * diagnóstico capturado.
     */
    public function cancelar(Request $request, Muestra $muestra): RedirectResponse
    {
        $user = $request->user();
        $this->autorizarAnalisis($user, $muestra);

        $diagnostico = $muestra->diagnostico;
        abort_unless($this->puedeCancelar($user, $diagnostico), 403);

        if ($diagnostico) {
            $diagnostico->update(['estado' => Diagnostico::ESTADO_CANCELADO]);
        } else {
            Diagnostico::create([
                'muestra_id' => $muestra->id,
                'estado' => Diagnostico::ESTADO_CANCELADO,
                'created_by' => $user->id,
            ]);
        }

        return redirect()->route('diagnosticos.index')->with('success', 'Análisis cancelado.');
    }

    /**
     * @return array{fecha_realizacion: string, resultado: ?string, muestras: list<array<string, mixed>>}
     */
    private function validarDiagnostico(Request $request, Muestra $muestra, bool $emitir): array
    {
        // El DatePickerInput envía las fechas como aaaa/mm/dd.
        $request->merge(['fecha_realizacion' => str_replace('/', '-', (string) $request->input('fecha_realizacion'))]);

        $fechaToma = $muestra->historiaClinica?->fecha_muestra;

        $validated = $request->validate([
            'fecha_realizacion' => array_filter([
                'required', 'date',
                $fechaToma ? 'after_or_equal:'.Carbon::parse($fechaToma)->toDateString() : null,
            ]),
            'muestras' => [$emitir ? 'required' : 'present', 'array'],
            'muestras.*.cantidad' => ['required', 'integer', 'min:1'],
            'muestras.*.identificacion' => ['required', 'string', 'max:150'],
            'muestras.*.resultado' => ['required', Rule::in(Diagnostico::RESULTADOS)],
            'muestras.*.aretes' => ['nullable', 'string', 'max:255'],
            // Tope del HTML para no guardar basura; el límite real (520) es
            // sobre el texto sin formato y se revisa abajo.
            'resultado' => ['nullable', 'string', 'max:10000'],
        ], [
            'fecha_realizacion.after_or_equal' => 'La fecha de realización no puede ser anterior a la fecha de toma.',
            'muestras.required' => 'Captura al menos una muestra antes de emitir.',
            'muestras.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'muestras.*.identificacion.required' => 'Falta la identificación.',
            'muestras.*.resultado.required' => 'Elige el resultado.',
        ]);

        $total = collect($validated['muestras'])->sum('cantidad');
        if ($total > $muestra->cantidad) {
            throw ValidationException::withMessages([
                'muestras' => "Se capturaron {$total} muestras y la historia clínica sólo trae {$muestra->cantidad}.",
            ]);
        }

        // Si se capturan aretes, tiene que haber uno por muestra de la fila.
        $erroresAretes = collect($validated['muestras'])
            ->map(fn ($m, $i) => [$i, count($this->aretes($m['aretes'] ?? null)), (int) $m['cantidad']])
            ->filter(fn ($fila) => $fila[1] > 0 && $fila[1] !== $fila[2])
            ->mapWithKeys(fn ($fila) => [
                "muestras.{$fila[0]}.aretes" => "Se capturaron {$fila[1]} aretes para {$fila[2]} muestras.",
            ]);
        if ($erroresAretes->isNotEmpty()) {
            throw ValidationException::withMessages($erroresAretes->all());
        }

        $resultado = $this->limpiarResultado($validated['resultado'] ?? null);
        if ($resultado !== null && mb_strlen($this->textoPlano($resultado)) > Diagnostico::MAXIMO_RESULTADO) {
            throw ValidationException::withMessages([
                'resultado' => 'El resultado no puede pasar de '.Diagnostico::MAXIMO_RESULTADO.' caracteres.',
            ]);
        }

        return [
            'fecha_realizacion' => $validated['fecha_realizacion'],
            'resultado' => $resultado,
            'muestras' => collect($validated['muestras'])
                ->map(fn ($m) => [
                    'cantidad' => (int) $m['cantidad'],
                    'identificacion' => $m['identificacion'],
                    'resultado' => $m['resultado'],
                    'aretes' => $m['aretes'] ?? null,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * El resultado llega como HTML del editor: se conservan sólo las
     * etiquetas de formato y se les quitan los atributos (estilos, eventos).
     */
    private function limpiarResultado(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $limpio = strip_tags($html, self::ETIQUETAS_RESULTADO);
        $limpio = preg_replace('/<(\w+)\b[^>]*>/', '<$1>', $limpio);

        return trim($this->textoPlano($limpio)) === '' ? null : $limpio;
    }

    /**
     * Aretes de una fila, separados por espacios, comas o punto y coma.
     *
     * @return list<string>
     */
    private function aretes(?string $aretes): array
    {
        return preg_split('/[\s,;]+/', (string) $aretes, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function textoPlano(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** "2026/10/29" o "2026-10-29" → "2026-10-29"; cualquier otra cosa → null. */
    private function fechaFiltro(string $valor): ?string
    {
        $fecha = str_replace('/', '-', $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : null;
    }

    private function puedeEntrar(User $user): bool
    {
        return collect(self::PERMISOS_LISTADO)->contains(fn ($clave) => $user->tienePermisoGranular($clave));
    }

    /**
     * El análisis tiene que ser de un área del usuario, y un diagnóstico
     * capturado por otro sólo se abre con "consultar de otros".
     */
    private function autorizarAnalisis(User $user, Muestra $muestra): void
    {
        abort_unless($this->puedeEntrar($user), 403);

        $areaId = $muestra->servicio?->area_id;
        abort_unless($areaId && $this->areasDe($user)->contains('id', $areaId), 403);

        $creadoPor = $muestra->diagnostico?->created_by;
        abort_if(
            $creadoPor && $creadoPor !== $user->id && ! $user->tienePermisoGranular('diagnosticos.consultar_de_otros'),
            403,
        );
    }

    private function puedeGuardar(User $user, ?Diagnostico $diagnostico): bool
    {
        return match ($diagnostico?->estado) {
            null => $user->tienePermisoGranular('diagnosticos.agregar'),
            Diagnostico::ESTADO_EN_PROCESO => $user->tienePermisoGranular('diagnosticos.modificar'),
            Diagnostico::ESTADO_EMITIDO => $user->tienePermisoGranular('diagnosticos.modificar_emitidos'),
            default => false,
        };
    }

    private function puedeEmitir(User $user, ?Diagnostico $diagnostico): bool
    {
        return $user->tienePermisoGranular('diagnosticos.emitir') && match ($diagnostico?->estado) {
            Diagnostico::ESTADO_CANCELADO => false,
            Diagnostico::ESTADO_EMITIDO => $user->tienePermisoGranular('diagnosticos.modificar_emitidos'),
            default => true,
        };
    }

    private function puedeCancelar(User $user, ?Diagnostico $diagnostico): bool
    {
        return $user->tienePermisoGranular('diagnosticos.cancelar')
            && $diagnostico?->estado !== Diagnostico::ESTADO_CANCELADO;
    }

    /**
     * Áreas cuyos análisis puede ver el usuario: las que tiene asignadas, o
     * todas si es administrador.
     */
    private function areasDe(User $user)
    {
        $query = $user->es_admin ? Area::query() : $user->areas();

        return $query->orderBy('nombre')->get(['areas.id', 'areas.nombre']);
    }
}
