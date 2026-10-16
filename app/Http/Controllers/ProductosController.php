<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only(['buscar', 'servicio_id', 'activo']);

        $productos = Producto::with('servicio')
            ->when($filtros['buscar'] ?? null, fn ($query, $buscar) => $query->where('nombre', 'like', "%{$buscar}%"))
            ->when($filtros['servicio_id'] ?? null, fn ($query, $servicioId) => $query->where('servicio_id', $servicioId))
            ->when(in_array($filtros['activo'] ?? null, ['0', '1'], true), fn ($query) => $query->where('activo', $filtros['activo'] === '1'))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogos/Productos/Index', [
            'productos' => $productos,
            'servicios' => Servicio::orderBy('descripcion')->get(['id', 'clave', 'descripcion']),
            'filtros' => $filtros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/Productos/Create', [
            'servicios' => Servicio::orderBy('descripcion')->get(['id', 'clave', 'descripcion']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Producto::create($this->validado($request));

        return redirect()->route('productos.index')->with('success', 'Producto creado.');
    }

    public function edit(Producto $producto): Response
    {
        return Inertia::render('Catalogos/Productos/Edit', [
            'producto' => $producto,
            'servicios' => Servicio::orderBy('descripcion')->get(['id', 'clave', 'descripcion']),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validado($request));

        return redirect()->route('productos.index')->with('success', 'Producto actualizado.');
    }

    private function validado(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'servicio_id' => ['nullable', 'integer', 'exists:servicios,id'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'unidad_precio' => ['required', Rule::in(Producto::UNIDADES_PRECIO)],
            'activo' => ['boolean'],
        ]);
    }
}
