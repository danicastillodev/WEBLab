<?php

namespace App\Http\Controllers;

use App\Models\HistoriaClinica;
use App\Models\ParametroOperacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParametrosOperacionController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Configuracion/ParametrosOperacion/Edit', [
            'parametros' => ParametroOperacion::first() ?? new ParametroOperacion([
                'bd_ruta' => '192.168.1.50',
                'bd_nombre' => 'NETLAB',
                'bd_puerto' => 1433,
                'bd_tiempo_espera' => 15,
                'impresora_tickets' => 'Ticket',
                'folio_automatico_activo' => true,
                'proximo_folio' => 55351,
                'iva_porcentaje' => 16,
            ]),
            // El número de caso siempre se configura para el ejercicio en curso;
            // se muestra el que tomará la próxima historia clínica, que es a la
            // vez el mínimo permitido: dentro de un ejercicio solo puede subir.
            'numeroCaso' => [
                'ejercicio' => now()->year,
                'proximo' => HistoriaClinica::siguienteNumeroCaso(now()->year),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $ejercicio = now()->year;

        // El mínimo es el próximo número vigente (el último caso registrado + 1,
        // o el ya configurado si es mayor), así que el valor nunca disminuye
        // dentro del ejercicio. Si no se envía, se conserva el actual.
        $minimoNumeroCaso = HistoriaClinica::siguienteNumeroCaso($ejercicio);

        $validated = $request->validate([
            'folio_automatico_activo' => ['boolean'],
            'proximo_folio' => ['required', 'integer', 'min:1'],
            'iva_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'proximo_numero_caso' => ['sometimes', 'required', 'integer', 'min:'.$minimoNumeroCaso],
        ], [
            'proximo_numero_caso.min' => "El próximo número de caso no puede disminuir en el ejercicio {$ejercicio}: debe ser al menos :min.",
        ]);

        if (array_key_exists('proximo_numero_caso', $validated)) {
            $validated['ejercicio_numero_caso'] = $ejercicio;
        }

        $registro = ParametroOperacion::first();

        if ($registro) {
            $registro->update($validated);
        } else {
            ParametroOperacion::create($validated);
        }

        return redirect()->route('parametros-operacion.edit')->with('success', 'Parámetros de operación actualizados.');
    }
}
