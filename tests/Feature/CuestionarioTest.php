<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Pregunta;
use App\Models\Propietario;
use App\Models\Prueba;
use App\Models\Raza;
use App\Models\Respuesta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuestionarioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    /** @return array{0: array<string, mixed>, 1: Especie} */
    private function datosHistoria(): array
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
        // Las especies ya vienen sembradas por su migración.
        $especie = Especie::firstOrCreate(['nombre' => 'Ave']);
        $raza = Raza::firstOrCreate(['nombre' => 'Leghorn', 'especie_id' => $especie->id]);
        $prueba = Prueba::firstOrCreate(['nombre' => 'Coprológico', 'especie_id' => $especie->id]);

        return [[
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => now()->toDateString(),
            'edad_unidad' => 'NA',
            'muestras' => [['prueba_id' => $prueba->id, 'cantidad' => 1]],
        ], $especie];
    }

    public function test_las_cinco_preguntas_iniciales_quedan_sembradas(): void
    {
        $this->assertSame(5, Pregunta::vigentes()->count());
        $this->assertSame('si_no', Pregunta::where('orden', 2)->value('tipo'));
        $this->assertSame('numero', Pregunta::where('orden', 3)->value('tipo'));
    }

    public function test_guarda_las_respuestas_junto_con_la_historia(): void
    {
        [$datos] = $this->datosHistoria();
        $preguntas = Pregunta::vigentes()->get();

        $datos['respuestas'] = [
            $preguntas[0]->id => 'Vacunación mensual',
            $preguntas[1]->id => 'Sí',
            $preguntas[2]->id => '250',
        ];

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $datos)
            ->assertRedirect();

        $historia = HistoriaClinica::latest('id')->first();

        $this->assertSame(3, $historia->respuestas()->count());
        $this->assertDatabaseHas('respuestas', [
            'historia_clinica_id' => $historia->id,
            'pregunta_id' => $preguntas[1]->id,
            'respuesta' => 'Sí',
        ]);
    }

    public function test_ignora_respuestas_vacias_y_preguntas_inexistentes(): void
    {
        [$datos] = $this->datosHistoria();
        $pregunta = Pregunta::vigentes()->first();

        $datos['respuestas'] = [
            $pregunta->id => 'Una respuesta',
            999999 => 'Pregunta que no existe',
            (string) Pregunta::vigentes()->skip(1)->first()->id => '   ',
        ];

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $datos)
            ->assertRedirect();

        $historia = HistoriaClinica::latest('id')->first();

        $this->assertSame(1, $historia->respuestas()->count());
        $this->assertSame('Una respuesta', $historia->respuestas()->first()->respuesta);
    }

    public function test_solo_se_ofrecen_las_preguntas_activas(): void
    {
        Pregunta::vigentes()->first()->update(['activa' => false]);

        $this->actingAs($this->admin())
            ->get(route('historias-clinicas.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('preguntas', 4));
    }

    public function test_crea_y_edita_una_pregunta(): void
    {
        $this->actingAs($this->admin())
            ->post(route('cuestionario.store'), [
                'texto' => '¿Usan desinfectante en la entrada?', 'tipo' => 'si_no', 'activa' => true,
            ])
            ->assertRedirect(route('cuestionario.index'));

        $pregunta = Pregunta::where('tipo', 'si_no')->latest('id')->first();

        $this->actingAs($this->admin())
            ->patch(route('cuestionario.update', $pregunta->id), [
                'texto' => '¿Usan tapete sanitario?', 'tipo' => 'si_no', 'orden' => 9, 'activa' => true,
            ])
            ->assertRedirect(route('cuestionario.index'));

        $this->assertSame('¿Usan tapete sanitario?', $pregunta->fresh()->texto);
    }

    /**
     * Borrar una pregunta contestada perdería el histórico, así que el
     * controlador la desactiva en lugar de eliminarla.
     */
    public function test_una_pregunta_con_respuestas_se_desactiva_en_vez_de_borrarse(): void
    {
        [$datos] = $this->datosHistoria();
        $pregunta = Pregunta::vigentes()->first();
        $datos['respuestas'] = [$pregunta->id => 'Contestada'];

        $this->actingAs($this->admin())->post(route('historias-clinicas.store'), $datos);

        $this->actingAs($this->admin())
            ->delete(route('cuestionario.destroy', $pregunta->id))
            ->assertRedirect(route('cuestionario.index'));

        $this->assertDatabaseHas('preguntas', ['id' => $pregunta->id, 'activa' => false]);
        $this->assertSame(1, Respuesta::where('pregunta_id', $pregunta->id)->count());
    }

    public function test_una_pregunta_sin_respuestas_si_se_elimina(): void
    {
        $pregunta = Pregunta::create(['texto' => 'Temporal', 'tipo' => 'texto', 'orden' => 99]);

        $this->actingAs($this->admin())
            ->delete(route('cuestionario.destroy', $pregunta->id))
            ->assertRedirect(route('cuestionario.index'));

        $this->assertDatabaseMissing('preguntas', ['id' => $pregunta->id]);
    }
}
