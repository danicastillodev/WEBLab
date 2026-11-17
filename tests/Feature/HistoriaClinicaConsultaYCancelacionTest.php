<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Consultar" (vista de solo lectura) y "Cancelar" (baja lógica vía cambio de
 * estado) sustituyen a "Editar" y "Eliminar" en el listado de historias
 * clínicas.
 */
class HistoriaClinicaConsultaYCancelacionTest extends TestCase
{
    use RefreshDatabase;

    private function historia(array $atributos = []): HistoriaClinica
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id, 'calle' => 'Av. Central',
            'numero_exterior' => '10', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id,
            'codigo_postal' => '44100',
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Bovino']);
        $raza = Raza::firstOrCreate(['nombre' => 'Holstein', 'especie_id' => $especie->id]);

        return HistoriaClinica::create(array_merge([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => '2026-05-05',
            'edad_unidad' => 'NA',
        ], $atributos));
    }

    public function test_consultar_muestra_la_vista_de_solo_lectura(): void
    {
        $historia = $this->historia();

        $respuesta = $this->actingAs(User::factory()->create(['es_admin' => true]))
            ->get(route('historias-clinicas.show', $historia))
            ->assertOk();

        $respuesta->assertInertia(fn ($page) => $page
            ->component('HistoriasClinicas/Show')
            ->where('historia.id', $historia->id)
        );
    }

    /**
     * Las fechas de realización y emisión de cada análisis vienen de su
     * diagnóstico; un análisis sin diagnóstico llega sin ellas.
     */
    public function test_consultar_incluye_las_fechas_del_diagnostico_de_cada_analisis(): void
    {
        $historia = $this->historia();
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $conDiagnostico = $historia->muestras()->create(['servicio_id' => $servicio->id, 'cantidad' => 1]);
        $historia->muestras()->create(['servicio_id' => $servicio->id, 'cantidad' => 2]);
        Diagnostico::create([
            'muestra_id' => $conDiagnostico->id,
            'fecha_realizacion' => '2026-11-10',
            'fecha_emision' => '2026-11-11 09:30:00',
            'estado' => Diagnostico::ESTADO_EMITIDO,
        ]);

        $this->actingAs(User::factory()->create(['es_admin' => true]))
            ->get(route('historias-clinicas.show', $historia))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('historia.muestras.0.diagnostico.fecha_realizacion', '2026-11-10')
                ->where('historia.muestras.0.diagnostico.fecha_emision', '2026-11-11 09:30:00')
                ->where('historia.muestras.1.diagnostico', null)
            );
    }

    public function test_cancelar_cambia_el_estado_en_vez_de_borrar_el_registro(): void
    {
        $historia = $this->historia();

        $response = $this->actingAs(User::factory()->create(['es_admin' => true]))
            ->patch(route('historias-clinicas.cancelar', $historia));

        // Redirige con los filtros por defecto (no a la URL pelada) para que
        // el índice no vuelva a redirigir por su cuenta y se pierda el flash.
        $this->assertStringStartsWith(route('historias-clinicas.index'), $response->headers->get('Location'));
        $response->assertSessionHas('success', 'Historia clínica cancelada.');

        $this->assertDatabaseHas('historias_clinicas', [
            'id' => $historia->id,
            'estado' => HistoriaClinica::ESTADO_CANCELADA,
        ]);
    }
}
