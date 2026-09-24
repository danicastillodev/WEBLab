<?php

namespace App\Http\Controllers;

use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administra el catálogo de preguntas del cuestionario que acompaña a cada
 * historia clínica. Las preguntas cambian con el tiempo, por eso una vez
 * contestadas no se borran: se desactivan para conservar el histórico.
 */
class CuestionarioController extends Controller
{
    private const REGLAS = [
        'texto' => ['required', 'string', 'max:500'],
        'tipo' => ['required', 'in:texto,si_no,numero'],
        'orden' => ['nullable', 'integer', 'min:0', 'max:65535'],
        'activa' => ['boolean'],
    ];

    public function index(): Response
    {
        return Inertia::render('Cuestionario/Index', [
            'preguntas' => Pregunta::withCount('respuestas')
                ->orderBy('orden')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cuestionario/Create', [
            'siguienteOrden' => (int) Pregunta::max('orden') + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(self::REGLAS);
        $validated['orden'] ??= (int) Pregunta::max('orden') + 1;

        Pregunta::create($validated);

        return redirect()->route('cuestionario.index')->with('success', 'Pregunta creada.');
    }

    public function edit(Pregunta $pregunta): Response
    {
        return Inertia::render('Cuestionario/Edit', [
            'pregunta' => $pregunta->loadCount('respuestas'),
        ]);
    }

    public function update(Request $request, Pregunta $pregunta): RedirectResponse
    {
        $pregunta->update($request->validate(self::REGLAS));

        return redirect()->route('cuestionario.index')->with('success', 'Pregunta actualizada.');
    }

    public function destroy(Pregunta $pregunta): RedirectResponse
    {
        // Borrarla arrastraría respuestas ya capturadas; en ese caso se desactiva.
        if (Respuesta::where('pregunta_id', $pregunta->id)->exists()) {
            $pregunta->update(['activa' => false]);

            return redirect()->route('cuestionario.index')
                ->with('success', 'La pregunta ya tiene respuestas registradas, así que se desactivó en lugar de eliminarse.');
        }

        $pregunta->delete();

        return redirect()->route('cuestionario.index')->with('success', 'Pregunta eliminada.');
    }
}
