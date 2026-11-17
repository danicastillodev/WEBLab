<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Muestra;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hoja de resultados (F070): una página por análisis con diagnóstico emitido.
 */
class ReporteResultadosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private HistoriaClinica $historia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['es_admin' => true]);

        $propietario = Propietario::create(['nombre' => 'Guillermo', 'apellidos' => 'Reyes']);
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Tonalá', 'estado_id' => $estado->id]);
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id, 'calle' => 'Hidalgo', 'numero_exterior' => '108',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id,
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Caprino']);

        $this->historia = HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => Raza::firstOrCreate(['nombre' => 'Varias', 'especie_id' => $especie->id])->id,
            'fecha_recepcion' => '2026-08-27 10:00:00',
            'edad_unidad' => 'NR',
        ]);
    }

    private function analisis(string $clave, ?string $estado = null): Muestra
    {
        $servicio = Servicio::create(['clave' => $clave, 'siglas' => $clave, 'descripcion' => "Análisis {$clave}", 'activo' => true]);
        $muestra = $this->historia->muestras()->create(['servicio_id' => $servicio->id, 'cantidad' => 3]);

        if ($estado) {
            Diagnostico::create([
                'muestra_id' => $muestra->id,
                'estado' => $estado,
                'fecha_realizacion' => '2026-08-28',
                'fecha_emision' => $estado === Diagnostico::ESTADO_EMITIDO ? '2026-08-28 11:50:00' : null,
                'resultado' => '<p>3 sueros negativos</p>',
            ]);
        }

        return $muestra;
    }

    public function test_sin_diagnosticos_emitidos_no_hay_hoja(): void
    {
        $this->analisis('BRT', Diagnostico::ESTADO_EN_PROCESO);

        $this->actingAs($this->admin)
            ->get(route('historias-clinicas.resultados', $this->historia))
            ->assertNotFound();
    }

    public function test_genera_una_hoja_por_analisis_emitido_y_cuenta_la_impresion(): void
    {
        $emitido = $this->analisis('BRT', Diagnostico::ESTADO_EMITIDO);
        $this->analisis('COPRO', Diagnostico::ESTADO_EN_PROCESO);
        $this->analisis('HEM');

        $this->actingAs($this->admin)
            ->get(route('historias-clinicas.resultados', $this->historia))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HistoriasClinicas/Resultados')
                ->has('analisis', 1)
                ->where('analisis.0.id', $emitido->id)
                ->where('analisis.0.servicio.siglas', 'BRT')
                ->where('analisis.0.diagnostico.impresiones', 1)
                ->where('historia.numero_caso', $this->historia->numero_caso));

        $this->assertSame(1, $emitido->diagnostico->fresh()->impresiones);
        // Quedan análisis por emitir: la historia está impresa en parte.
        $this->assertSame(HistoriaClinica::IMPRESO_PARCIAL, $this->historia->fresh()->impreso);
    }

    public function test_con_todo_emitido_la_historia_queda_impresa_y_las_reimpresiones_se_cuentan(): void
    {
        $emitido = $this->analisis('BRT', Diagnostico::ESTADO_EMITIDO);
        // Un análisis cancelado no se espera para dar la historia por impresa.
        $this->analisis('COPRO', Diagnostico::ESTADO_CANCELADO);

        $this->actingAs($this->admin)->get(route('historias-clinicas.resultados', $this->historia))->assertOk();
        $this->actingAs($this->admin)
            ->get(route('historias-clinicas.resultados', $this->historia))
            ->assertInertia(fn ($page) => $page->where('analisis.0.diagnostico.impresiones', 2));

        $this->assertSame(2, $emitido->diagnostico->fresh()->impresiones);
        $this->assertSame(HistoriaClinica::IMPRESO_SI, $this->historia->fresh()->impreso);
    }

    public function test_requiere_permiso_para_ver_historias(): void
    {
        $this->analisis('BRT', Diagnostico::ESTADO_EMITIDO);

        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('historias-clinicas.resultados', $this->historia))
            ->assertForbidden();
    }
}
