<?php

namespace App\Http\Controllers;

use App\Models\TipoMuestra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catálogo general de tipos de muestra (los que se relacionan con los
 * análisis). Los tipos de muestra ligados a una prueba son históricos y no se
 * administran desde aquí.
 */
class TiposMuestraController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/TiposMuestra/Index', [
            'tiposMuestra' => TipoMuestra::general()->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalogos/TiposMuestra/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        TipoMuestra::create($this->validado($request));

        return redirect()->route('tipos-muestra.index')->with('success', 'Tipo de muestra creado.');
    }

    public function edit(TipoMuestra $tipoMuestra): Response
    {
        abort_unless($tipoMuestra->prueba_id === null, 404);

        return Inertia::render('Catalogos/TiposMuestra/Edit', [
            'tipoMuestra' => $tipoMuestra,
        ]);
    }

    public function update(Request $request, TipoMuestra $tipoMuestra): RedirectResponse
    {
        abort_unless($tipoMuestra->prueba_id === null, 404);

        $tipoMuestra->update($this->validado($request, $tipoMuestra));

        return redirect()->route('tipos-muestra.index')->with('success', 'Tipo de muestra actualizado.');
    }

    public function destroy(TipoMuestra $tipoMuestra): RedirectResponse
    {
        abort_unless($tipoMuestra->prueba_id === null, 404);

        $tipoMuestra->delete();

        return redirect()->route('tipos-muestra.index')->with('success', 'Tipo de muestra eliminado.');
    }

    private function validado(Request $request, ?TipoMuestra $tipoMuestra = null): array
    {
        // Igual que en el alta rápida desde el análisis: se guarda en mayúsculas.
        $request->merge(['nombre' => mb_strtoupper(trim((string) $request->input('nombre')))]);

        return $request->validate([
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('tipo_muestras', 'nombre')->whereNull('prueba_id')->ignore($tipoMuestra),
            ],
        ]);
    }
}
