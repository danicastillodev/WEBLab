<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\Propietario;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PropietariosController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Propietarios/Index', [
            'propietarios' => Propietario::withCount(['direcciones', 'explotaciones'])
                ->orderBy('apellidos')
                ->orderBy('nombre')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Propietarios/Create', [
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($validated) {
            $propietario = Propietario::create($this->datosPropietario($validated));
            $this->sincronizar($propietario->direcciones(), $validated['direcciones']);
            $this->sincronizar($propietario->explotaciones(), $validated['explotaciones'] ?? []);
        });

        return redirect()->route('propietarios.index')->with('success', 'Propietario creado.');
    }

    public function edit(Propietario $propietario): Response
    {
        return Inertia::render('Catalogos/Propietarios/Edit', [
            'propietario' => $propietario->load([
                'direcciones',
                'explotaciones' => fn ($q) => $q->orderBy('nombre'),
            ]),
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function update(Request $request, Propietario $propietario): RedirectResponse
    {
        $validated = $request->validate($this->reglas($propietario), $this->mensajes());

        // Lo que se quita del formulario se borra, salvo que ya lo use una
        // historia clínica o una nota de pago: borrarlo rompería ese registro
        // (direccion_id en historias es RESTRICT; explotacion_id se anularía).
        $this->bloquearSiEnUso(
            'direcciones',
            $this->quitadas($propietario->direcciones(), $validated['direcciones']),
            fn ($ids) => HistoriaClinica::whereIn('direccion_id', $ids)->pluck('direccion_id')
                ->merge(NotaPago::whereIn('direccion_id', $ids)->pluck('direccion_id')),
            'La dirección :nombre está usada en historias clínicas o notas de pago; no se puede quitar.',
            fn ($d) => trim("{$d->calle} {$d->numero_exterior}"),
        );
        $this->bloquearSiEnUso(
            'explotaciones',
            $this->quitadas($propietario->explotaciones(), $validated['explotaciones'] ?? []),
            fn ($ids) => HistoriaClinica::whereIn('explotacion_id', $ids)->pluck('explotacion_id'),
            'La explotación ":nombre" está usada en historias clínicas; no se puede quitar.',
            fn ($e) => $e->nombre,
        );

        DB::transaction(function () use ($propietario, $validated) {
            $propietario->update($this->datosPropietario($validated));
            $this->sincronizar($propietario->direcciones(), $validated['direcciones']);
            $this->sincronizar($propietario->explotaciones(), $validated['explotaciones'] ?? []);
        });

        return redirect()->route('propietarios.index')->with('success', 'Propietario actualizado.');
    }

    public function destroy(Propietario $propietario): RedirectResponse
    {
        $propietario->delete();

        return redirect()->route('propietarios.index')->with('success', 'Propietario eliminado.');
    }

    /**
     * Con $propietario (edición) cada fila puede traer el id de una dirección
     * o explotación suya; sin id es una nueva.
     */
    private function reglas(?Propietario $propietario = null): array
    {
        $propia = fn (string $tabla) => $propietario
            ? ['nullable', 'integer', Rule::exists($tabla, 'id')->where('propietario_id', $propietario->id)]
            : ['prohibited'];

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp' => ['nullable', 'string', 'size:18', Rule::unique('propietarios', 'curp')->ignore($propietario?->id)],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', Rule::unique('propietarios', 'rfc')->ignore($propietario?->id)],
            'telefono' => ['nullable', 'digits:10'],
            'direcciones' => ['required', 'array', 'min:1'],
            'direcciones.*.id' => $propia('direcciones'),
            'direcciones.*.calle' => ['required', 'string', 'max:200'],
            'direcciones.*.numero_exterior' => ['required', 'string', 'max:20'],
            'direcciones.*.numero_interior' => ['nullable', 'string', 'max:20'],
            'direcciones.*.colonia' => ['required', 'string', 'max:150'],
            'direcciones.*.estado_id' => ['required', 'integer', 'exists:estados,id'],
            'direcciones.*.municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'direcciones.*.codigo_postal' => ['required', 'string', 'max:10'],
            'direcciones.*.caseta' => ['nullable', 'string', 'max:50'],
            'direcciones.*.lote' => ['nullable', 'string', 'max:50'],
            'direcciones.*.parvada' => ['nullable', 'string', 'max:50'],
            'explotaciones' => ['nullable', 'array'],
            'explotaciones.*.id' => $propia('explotaciones'),
            'explotaciones.*.nombre' => ['required', 'string', 'max:255'],
            'explotaciones.*.direccion' => ['nullable', 'string', 'max:255'],
            'explotaciones.*.estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'explotaciones.*.municipio_id' => ['nullable', 'integer', 'exists:municipios,id'],
            'explotaciones.*.caseta' => ['nullable', 'string', 'max:50'],
            'explotaciones.*.lote' => ['nullable', 'string', 'max:50'],
            'explotaciones.*.parvada' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique' => 'Ya existe un propietario con ese RFC.',
            'telefono.digits' => 'El teléfono debe tener exactamente 10 dígitos.',
            'direcciones.min' => 'Se requiere al menos una dirección.',
            'explotaciones.*.nombre.required' => 'La explotación necesita un nombre.',
        ];
    }

    private function datosPropietario(array $validated): array
    {
        return [
            'nombre' => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp' => isset($validated['curp']) ? strtoupper($validated['curp']) : null,
            'rfc' => isset($validated['rfc']) ? strtoupper($validated['rfc']) : null,
            'telefono' => $validated['telefono'] ?? null,
        ];
    }

    /**
     * Ids que el propietario tiene guardados y ya no vienen en el formulario.
     */
    private function quitadas(HasMany $relacion, array $filas): array
    {
        return (clone $relacion)->pluck('id')->diff(array_filter(array_column($filas, 'id')))->values()->all();
    }

    private function bloquearSiEnUso(string $campo, array $ids, callable $enUso, string $mensaje, callable $etiqueta): void
    {
        if (! $ids) {
            return;
        }

        $usadas = collect($enUso($ids))->unique()->values();
        if ($usadas->isEmpty()) {
            return;
        }

        $modelo = DB::table($campo)->whereIn('id', $usadas)->get();
        throw ValidationException::withMessages([
            $campo => $modelo->map(fn ($fila) => str_replace(':nombre', $etiqueta($fila), $mensaje))->all(),
        ]);
    }

    /**
     * Deja la relación igual a $filas: actualiza las que traen id, crea las
     * que no y borra las que ya no están. Las filas conservan su id, así que
     * las historias que las usan siguen apuntando al mismo registro.
     */
    private function sincronizar(HasMany $relacion, array $filas): void
    {
        (clone $relacion)->whereKeyNot(array_filter(array_column($filas, 'id')))->delete();

        $guardadas = (clone $relacion)->get()->keyBy('id');
        foreach ($filas as $fila) {
            $id = $fila['id'] ?? null;
            unset($fila['id']);

            $id ? $guardadas[$id]->update($fila) : $relacion->create($fila);
        }
    }
}
