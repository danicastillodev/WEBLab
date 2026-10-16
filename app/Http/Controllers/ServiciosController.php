<?php

namespace App\Http\Controllers;

use App\Models\Analista;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Metodo;
use App\Models\Norma;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiciosController extends Controller
{
    /**
     * Catálogos que se pueden ampliar desde el formulario del análisis.
     */
    private const CATALOGOS = [
        'metodo' => [Metodo::class, 'metodos'],
        'categoria' => [Categoria::class, 'categorias'],
        'tipo-muestra' => [TipoMuestra::class, 'tipo_muestras'],
        'area' => [Area::class, 'areas'],
        'analista' => [Analista::class, 'analistas'],
        'norma' => [Norma::class, 'normas'],
    ];

    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'area_id', 'metodo_id']);
        // Por defecto se muestran todos; 'activos' o 'inactivos' filtran.
        $filtros['estado'] = in_array($request->input('estado'), ['activos', 'inactivos', 'todos'], true)
            ? $request->input('estado')
            : 'todos';

        $servicios = Servicio::with(['area', 'metodo'])
            ->when($filtros['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query->where('clave', 'like', "%{$buscar}%")
                        ->orWhere('siglas', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['area_id'] ?? null, fn ($query, $areaId) => $query->where('area_id', $areaId))
            ->when($filtros['metodo_id'] ?? null, fn ($query, $metodoId) => $query->where('metodo_id', $metodoId))
            ->when($filtros['estado'] !== 'todos', fn ($query) => $query->where('activo', $filtros['estado'] === 'activos'))
            ->orderBy('clave')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogos/Servicios/Index', [
            'servicios' => $servicios,
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
            'metodos' => Metodo::orderBy('nombre')->get(['id', 'nombre']),
            'filtros' => $filtros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Servicios/Create', [
            // Sólo informativo: el número definitivo se asigna al guardar.
            'siguienteNumero' => ((int) Servicio::max('numero')) + 1,
            ...$this->catalogos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validado($request);

        DB::transaction(function () use ($datos) {
            $servicio = Servicio::create($datos);
            $this->sincronizar($servicio, $datos);
        });

        return redirect()->route('servicios.index')->with('success', 'Análisis creado.');
    }

    public function edit(Servicio $servicio): Response
    {
        $servicio->load(['normas:id', 'tiposMuestra:id']);

        return Inertia::render('Catalogos/Servicios/Edit', [
            'servicio' => $servicio,
            ...$this->catalogos(),
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $datos = $this->validado($request, $servicio);

        DB::transaction(function () use ($servicio, $datos) {
            $servicio->update($datos);
            $this->sincronizar($servicio, $datos);
        });

        return redirect()->route('servicios.index')->with('success', 'Análisis actualizado.');
    }

    /**
     * Los análisis no se eliminan: se desactivan para conservar su historial
     * (muestras, productos, notas de pago) y dejan de ofrecerse en las
     * historias clínicas.
     */
    public function updateActivo(Request $request, Servicio $servicio): RedirectResponse
    {
        abort_unless($request->user()->puede('servicios', 'editar'), 403, 'No tiene permiso para realizar esta acción.');

        $servicio->update(['activo' => ! $servicio->activo]);

        return back()->with('success', $servicio->activo ? 'Análisis activado.' : 'Análisis desactivado.');
    }

    /**
     * Alta rápida de un registro de catálogo desde el formulario (vía axios).
     */
    public function storeCatalogo(Request $request, string $catalogo): JsonResponse
    {
        abort_unless(isset(self::CATALOGOS[$catalogo]), 404);

        [$modelo, $tabla] = self::CATALOGOS[$catalogo];

        $unico = Rule::unique($tabla, 'nombre');
        if ($catalogo === 'tipo-muestra') {
            $unico->whereNull('prueba_id');
        }

        // Los catálogos se guardan en mayúsculas: se normaliza antes de validar
        // para que la regla de unicidad compare contra el valor que se guardará.
        $request->merge(['nombre' => mb_strtoupper(trim((string) $request->input('nombre')))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150', $unico],
        ]);

        $registro = $modelo::create(['nombre' => $datos['nombre']]);

        return response()->json($registro->only(['id', 'nombre']), 201);
    }

    /**
     * Catálogos de selección única que se pueden modificar y eliminar desde el
     * formulario, con la columna de servicios que los referencia.
     */
    private const CATALOGOS_EDITABLES = [
        'metodo' => 'metodo_id',
        'categoria' => 'categoria_id',
        'area' => 'area_id',
        'analista' => 'analista_id',
    ];

    public function updateCatalogo(Request $request, string $catalogo, int $id): JsonResponse
    {
        abort_unless($request->user()->puede('servicios', 'editar'), 403, 'No tiene permiso para realizar esta acción.');
        $registro = $this->registroEditable($catalogo, $id);

        [, $tabla] = self::CATALOGOS[$catalogo];
        $request->merge(['nombre' => mb_strtoupper(trim((string) $request->input('nombre')))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique($tabla, 'nombre')->ignore($registro)],
        ]);

        $registro->update($datos);

        return response()->json($registro->only(['id', 'nombre']));
    }

    /**
     * Sólo se elimina si ningún análisis lo tiene asignado.
     */
    public function destroyCatalogo(Request $request, string $catalogo, int $id): JsonResponse
    {
        abort_unless($request->user()->puede('servicios', 'eliminar'), 403, 'No tiene permiso para realizar esta acción.');
        $registro = $this->registroEditable($catalogo, $id);

        $asignados = Servicio::where(self::CATALOGOS_EDITABLES[$catalogo], $registro->id)->count();
        abort_if(
            $asignados > 0,
            409,
            "No se puede eliminar \"{$registro->nombre}\": está asignado a {$asignados} análisis.",
        );

        $registro->delete();

        return response()->json(['id' => $registro->id]);
    }

    private function registroEditable(string $catalogo, int $id): Model
    {
        abort_unless(isset(self::CATALOGOS_EDITABLES[$catalogo]), 404);

        [$modelo] = self::CATALOGOS[$catalogo];

        return $modelo::findOrFail($id);
    }

    private function catalogos(): array
    {
        return [
            'metodos' => Metodo::orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'tiposMuestra' => TipoMuestra::general()->orderBy('nombre')->get(['id', 'nombre']),
            'areas' => Area::orderBy('nombre')->get(['id', 'nombre']),
            'analistas' => Analista::orderBy('nombre')->get(['id', 'nombre']),
            'normas' => Norma::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    private function sincronizar(Servicio $servicio, array $datos): void
    {
        $servicio->normas()->sync($datos['normas'] ?? []);
        $servicio->tiposMuestra()->sync($datos['tipos_muestra'] ?? []);
    }

    private function validado(Request $request, ?Servicio $servicio = null): array
    {
        return $request->validate([
            'clave' => ['required', 'string', 'max:20', Rule::unique('servicios')->ignore($servicio)],
            'siglas' => ['nullable', 'string', 'max:15'],
            'descripcion' => ['required', 'string', 'max:255'],
            'nombre_analisis' => ['nullable', 'string', 'max:255'],
            'metodo_id' => ['nullable', 'integer', 'exists:metodos,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'analista_id' => ['nullable', 'integer', 'exists:analistas,id'],
            'referencias' => ['nullable', 'string', 'max:255'],
            'compuesto' => ['boolean'],
            'independiente' => ['boolean'],
            'activo' => ['boolean'],
            'normas' => ['array'],
            'normas.*' => ['integer', 'exists:normas,id'],
            'tipos_muestra' => ['array'],
            'tipos_muestra.*' => ['integer', Rule::exists('tipo_muestras', 'id')->whereNull('prueba_id')],
        ]);
    }
}
