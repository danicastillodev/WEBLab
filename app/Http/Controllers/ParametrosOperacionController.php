<?php

namespace App\Http\Controllers;

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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'folio_automatico_activo' => ['boolean'],
            'proximo_folio' => ['required', 'integer', 'min:1'],
            'iva_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $registro = ParametroOperacion::first();

        if ($registro) {
            $registro->update($validated);
        } else {
            ParametroOperacion::create($validated);
        }

        return redirect()->route('parametros-operacion.edit')->with('success', 'Parámetros de operación actualizados.');
    }
}
