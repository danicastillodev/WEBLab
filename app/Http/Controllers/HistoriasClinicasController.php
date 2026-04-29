<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoriasClinicasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HistoriasClinicas/Index', [
            'historias' => HistoriaClinica::with(['propietario', 'especie', 'raza'])
                ->orderByDesc('fecha_recepcion')
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HistoriasClinicas/Create', [
            'propietarios' => Propietario::orderBy('apellidos')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
            'direcciones'  => Direccion::with('municipio')->get(['id', 'propietario_id', 'calle', 'numero_exterior', 'colonia', 'municipio_id']),
            'especies'     => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'razas'        => Raza::orderBy('nombre')->get(['id', 'nombre']),
            'estados'      => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios'   => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ]);
    }

    public function storePropietario(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'curp'      => ['required', 'string', 'size:18', 'unique:propietarios,curp'],
            'rfc'       => ['required', 'string', 'min:12', 'max:13', 'unique:propietarios,rfc'],
        ], [
            'curp.size'   => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Ya existe un propietario con esa CURP.',
            'rfc.min'     => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max'     => 'El RFC no puede tener más de 13 caracteres.',
            'rfc.unique'  => 'Ya existe un propietario con ese RFC.',
        ]);

        $propietario = Propietario::create([
            'nombre'    => $validated['nombre'],
            'apellidos' => $validated['apellidos'],
            'curp'      => strtoupper($validated['curp']),
            'rfc'       => strtoupper($validated['rfc']),
        ]);

        return response()->json($propietario);
    }

    public function storeDireccion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'propietario_id'  => ['required', 'integer', 'exists:propietarios,id'],
            'calle'           => ['required', 'string', 'max:200'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia'         => ['required', 'string', 'max:150'],
            'estado_id'       => ['required', 'integer', 'exists:estados,id'],
            'municipio_id'    => ['required', 'integer', 'exists:municipios,id'],
            'codigo_postal'   => ['required', 'string', 'max:10'],
        ]);

        $direccion = Direccion::create($validated);

        return response()->json($direccion->load('municipio'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id'  => ['required', 'integer', 'exists:propietarios,id'],
            'direccion_id'    => ['required', 'integer', 'exists:direcciones,id'],
            'fecha_recepcion' => ['required', 'date'],
            'especie_id'      => ['required', 'integer', 'exists:especies,id'],
            'raza_id'         => ['required', 'integer', 'exists:razas,id'],
            'edad_unidad'     => ['required', 'in:Dias,Meses,Años,NR,NA'],
            'edad_valor'      => ['nullable', 'integer', 'min:0'],
            'cantidad'        => ['required', 'integer', 'min:1'],
        ]);

        if (in_array($validated['edad_unidad'], ['NR', 'NA'])) {
            $validated['edad_valor'] = null;
        }

        HistoriaClinica::create($validated);

        return redirect()->route('historias-clinicas.index')->with('success', 'Historia clínica creada.');
    }

    public function edit(HistoriaClinica $historiaClinica): Response
    {
        return Inertia::render('HistoriasClinicas/Edit', [
            'historia'     => $historiaClinica->load(['propietario', 'direccion.municipio', 'especie', 'raza']),
            'propietarios' => Propietario::orderBy('apellidos')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
            'direcciones'  => Direccion::with('municipio')->get(['id', 'propietario_id', 'calle', 'numero_exterior', 'colonia', 'municipio_id']),
            'especies'     => Especie::orderBy('nombre')->get(['id', 'nombre']),
            'razas'        => Raza::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, HistoriaClinica $historiaClinica): RedirectResponse
    {
        $validated = $request->validate([
            'propietario_id'  => ['required', 'integer', 'exists:propietarios,id'],
            'direccion_id'    => ['required', 'integer', 'exists:direcciones,id'],
            'fecha_recepcion' => ['required', 'date'],
            'especie_id'      => ['required', 'integer', 'exists:especies,id'],
            'raza_id'         => ['required', 'integer', 'exists:razas,id'],
            'edad_unidad'     => ['required', 'in:Dias,Meses,Años,NR,NA'],
            'edad_valor'      => ['nullable', 'integer', 'min:0'],
            'cantidad'        => ['required', 'integer', 'min:1'],
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
