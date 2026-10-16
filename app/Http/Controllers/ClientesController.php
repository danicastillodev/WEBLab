<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo "Listado clientes" del menú Notas de pago: los mismos clientes
 * que se dan de alta desde el modal de la nota de pago.
 */
class ClientesController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'estado_id']);

        $clientes = Cliente::with(['estado:id,nombre', 'municipio:id,nombre'])
            ->withCount('notasPago')
            ->when($filtros['buscar'] ?? null, fn ($query, $buscar) => $query->where(
                fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")->orWhere('rfc', 'like', "%{$buscar}%")
            ))
            ->when($filtros['estado_id'] ?? null, fn ($query, $estadoId) => $query->where('estado_id', $estadoId))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogos/Clientes/Index', [
            'clientes' => $clientes,
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'filtros' => $filtros,
            'puede' => collect(['crear', 'editar', 'eliminar'])
                ->mapWithKeys(fn ($accion) => [$accion => $request->user()->puede('clientes', $accion)]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Clientes/Create', $this->catalogos());
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')->with('success', 'Cliente creado.');
    }

    public function edit(Cliente $cliente): Response
    {
        return Inertia::render('Catalogos/Clientes/Edit', ['cliente' => $cliente] + $this->catalogos());
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($cliente->notasPago()->exists()) {
            return back()->with('error', "No se puede eliminar a {$cliente->nombre}: ya tiene notas de pago.");
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'estados' => Estado::orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
        ];
    }
}
