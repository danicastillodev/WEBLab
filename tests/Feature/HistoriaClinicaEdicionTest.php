<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\Explotacion;
use App\Models\FuncionZootecnica;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Pregunta;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La edición de una historia clínica usa el mismo formulario que el alta,
 * precargado con la historia, y reemplaza sus análisis y respuestas.
 */
class HistoriaClinicaEdicionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Servicio $copro;

    private Servicio $hemograma;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['es_admin' => true]);
        $this->copro = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $this->hemograma = Servicio::create(['clave' => 'HEM', 'descripcion' => 'Hemograma', 'activo' => true]);
    }

    /** @return array<string, mixed> */
    private function datos(array $cambios = []): array
    {
        $propietario = Propietario::firstOrCreate(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        $direccion = Direccion::firstOrCreate(['propietario_id' => $propietario->id, 'calle' => 'Av. Central'], [
            'numero_exterior' => '10', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id,
        ]);
        $explotacion = Explotacion::firstOrCreate(['propietario_id' => $propietario->id, 'nombre' => 'Rancho Uno'], [
            'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Ave']);
        $raza = Raza::firstOrCreate(['nombre' => 'Leghorn', 'especie_id' => $especie->id]);
        $funcion = FuncionZootecnica::firstOrCreate(['nombre' => 'POSTURA']);
        $funcion->especies()->syncWithoutDetaching([$especie->id]);

        return array_merge([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'explotacion_id' => $explotacion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'funcion_zootecnica_id' => $funcion->id,
            'fecha_recepcion' => '2026-10-01',
            'fecha_muestra' => '2026-09-30',
            'edad_unidad' => 'NA',
            'muestras' => [['servicio_id' => $this->copro->id, 'cantidad' => 1]],
        ], $cambios);
    }

    private function historia(): HistoriaClinica
    {
        $this->actingAs($this->admin)
            ->post(route('historias-clinicas.store'), $this->datos())
            ->assertSessionHasNoErrors();

        return HistoriaClinica::latest('id')->firstOrFail();
    }

    public function test_el_formulario_de_edicion_llega_precargado(): void
    {
        $historia = $this->historia();

        $this->actingAs($this->admin)
            ->get(route('historias-clinicas.edit', $historia))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('HistoriasClinicas/Create')
                ->where('historia.id', $historia->id)
                ->has('historia.muestras', 1)
                ->where('historia.muestras.0.servicio_id', $this->copro->id)
                ->has('servicios')
                ->has('preguntas'));
    }

    public function test_guarda_los_cambios_y_reemplaza_los_analisis_y_respuestas(): void
    {
        $historia = $this->historia();
        $numeroCaso = $historia->numero_caso;
        $suero = TipoMuestra::create(['nombre' => 'SUERO']);
        $this->hemograma->tiposMuestra()->attach($suero);
        $pregunta = Pregunta::vigentes()->first();

        $this->actingAs($this->admin)
            ->put(route('historias-clinicas.update', $historia), $this->datos([
                'fecha_muestra' => '2026-09-28',
                'notas_adicionales' => 'Corregida',
                'muestras' => [
                    ['servicio_id' => $this->hemograma->id, 'cantidad' => 3, 'tipo_muestra_id' => $suero->id],
                ],
                'respuestas' => [$pregunta->id => 'Respuesta corregida'],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $historia->refresh();
        $this->assertSame($numeroCaso, $historia->numero_caso);
        $this->assertSame('Corregida', $historia->notas_adicionales);
        $this->assertStringStartsWith('2026-09-28', (string) $historia->fecha_muestra);

        $this->assertSame(1, $historia->muestras()->count());
        $this->assertDatabaseHas('muestras', [
            'historia_clinica_id' => $historia->id,
            'servicio_id' => $this->hemograma->id,
            'cantidad' => 3,
            'tipo_muestra_id' => $suero->id,
        ]);
        $this->assertDatabaseHas('respuestas', [
            'historia_clinica_id' => $historia->id,
            'pregunta_id' => $pregunta->id,
            'respuesta' => 'Respuesta corregida',
        ]);
    }

    /**
     * La recepción es el momento del alta, con hora; editar no la mueve.
     */
    public function test_la_fecha_de_recepcion_guarda_la_hora_del_alta(): void
    {
        $this->travelTo(now()->setDateTime(2026, 10, 20, 14, 35, 12));
        $historia = $this->historia();

        $this->assertSame('2026-10-20 14:35:12', $historia->getRawOriginal('fecha_recepcion'));

        $this->travelTo(now()->addDay());
        $this->actingAs($this->admin)
            ->put(route('historias-clinicas.update', $historia), $this->datos())
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-20 14:35:12', $historia->fresh()->getRawOriginal('fecha_recepcion'));
    }

    public function test_una_historia_cancelada_no_se_puede_editar(): void
    {
        $historia = $this->historia();
        $historia->update(['estado' => HistoriaClinica::ESTADO_CANCELADA]);

        $this->actingAs($this->admin)
            ->get(route('historias-clinicas.edit', $historia))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->put(route('historias-clinicas.update', $historia), $this->datos())
            ->assertForbidden();
    }
}
