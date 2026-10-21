<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\Estado;
use App\Models\Explotacion;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropietariosTest extends TestCase
{
    use RefreshDatabase;

    private Estado $estado;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->estado = Estado::firstOrCreate(['nombre' => 'Jalisco'], ['clave' => 'JAL']);
        $this->municipio = Municipio::firstOrCreate(['nombre' => 'Tonalá', 'estado_id' => $this->estado->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    private function direccion(array $extra = []): array
    {
        return array_merge([
            'calle' => 'Av. Central', 'numero_exterior' => '10', 'colonia' => 'Centro',
            'estado_id' => $this->estado->id, 'municipio_id' => $this->municipio->id, 'codigo_postal' => '44100',
        ], $extra);
    }

    private function datos(array $direcciones, array $explotaciones): array
    {
        return ['nombre' => 'Ana', 'apellidos' => 'Pérez', 'direcciones' => $direcciones, 'explotaciones' => $explotaciones];
    }

    /** Una historia mínima que usa la dirección y la explotación dadas. */
    private function historia(Propietario $propietario, int $direccionId, ?int $explotacionId = null): void
    {
        $especie = Especie::firstOrCreate(['nombre' => 'Bovinos']);
        DB::table('historias_clinicas')->insert([
            'ejercicio' => 2026, 'propietario_id' => $propietario->id, 'direccion_id' => $direccionId,
            'explotacion_id' => $explotacionId, 'fecha_recepcion' => now()->toDateString(),
            'especie_id' => $especie->id, 'raza_id' => Raza::firstOrCreate(['nombre' => 'Holstein', 'especie_id' => $especie->id])->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_crea_un_propietario_sin_direcciones(): void
    {
        $this->actingAs($this->admin())
            ->post(route('propietarios.store'), $this->datos([], []))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('propietarios.index'));

        $propietario = Propietario::where('nombre', 'Ana')->firstOrFail();
        $this->assertSame(0, $propietario->direcciones()->count());
    }

    public function test_la_colonia_de_la_direccion_es_opcional(): void
    {
        $this->actingAs($this->admin())
            ->post(route('propietarios.store'), $this->datos([$this->direccion(['colonia' => null])], []))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('direcciones', ['calle' => 'Av. Central', 'colonia' => null]);
    }

    public function test_crea_un_propietario_con_sus_explotaciones(): void
    {
        $this->actingAs($this->admin())
            ->post(route('propietarios.store'), $this->datos([$this->direccion()], [
                ['nombre' => 'Granja Uno', 'estado_id' => $this->estado->id, 'municipio_id' => $this->municipio->id, 'caseta' => '3'],
                ['nombre' => 'Granja Dos'],
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('propietarios.index'));

        $propietario = Propietario::where('nombre', 'Ana')->firstOrFail();
        $this->assertSame(['Granja Dos', 'Granja Uno'], $propietario->explotaciones()->orderBy('nombre')->pluck('nombre')->all());
        $this->assertDatabaseHas('explotaciones', ['nombre' => 'Granja Uno', 'caseta' => '3', 'municipio_id' => $this->municipio->id]);
    }

    public function test_al_editar_conserva_los_ids_y_agrega_edita_y_quita_explotaciones(): void
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $direccion = $propietario->direcciones()->create($this->direccion());
        $usada = $propietario->explotaciones()->create(['nombre' => 'Granja vieja']);
        $sobra = $propietario->explotaciones()->create(['nombre' => 'Para quitar']);
        // Antes, editar borraba y recreaba las direcciones: con una historia
        // usándola (FK RESTRICT) la edición fallaba.
        $this->historia($propietario, $direccion->id, $usada->id);

        $this->actingAs($this->admin())
            ->get(route('propietarios.edit', $propietario))
            ->assertInertia(fn ($page) => $page->has('propietario.explotaciones', 2));

        $this->actingAs($this->admin())
            ->patch(route('propietarios.update', $propietario), $this->datos(
                [$this->direccion(['id' => $direccion->id, 'calle' => 'Av. Nueva'])],
                [['id' => $usada->id, 'nombre' => 'Granja renombrada'], ['nombre' => 'Granja nueva']],
            ))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('propietarios.index'));

        $this->assertDatabaseHas('direcciones', ['id' => $direccion->id, 'calle' => 'Av. Nueva']);
        $this->assertDatabaseHas('explotaciones', ['id' => $usada->id, 'nombre' => 'Granja renombrada']);
        $this->assertDatabaseMissing('explotaciones', ['id' => $sobra->id]);
        $this->assertDatabaseHas('explotaciones', ['propietario_id' => $propietario->id, 'nombre' => 'Granja nueva']);
        $this->assertDatabaseHas('historias_clinicas', ['direccion_id' => $direccion->id, 'explotacion_id' => $usada->id]);
    }

    public function test_no_quita_una_explotacion_usada_en_historias(): void
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $direccion = $propietario->direcciones()->create($this->direccion());
        $usada = $propietario->explotaciones()->create(['nombre' => 'Granja usada']);
        $this->historia($propietario, $direccion->id, $usada->id);

        $this->actingAs($this->admin())
            ->patch(route('propietarios.update', $propietario), $this->datos([$this->direccion(['id' => $direccion->id])], []))
            ->assertSessionHasErrors('explotaciones');

        $this->assertDatabaseHas('explotaciones', ['id' => $usada->id]);
    }

    public function test_no_acepta_explotaciones_de_otro_propietario(): void
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $direccion = $propietario->direcciones()->create($this->direccion());
        $ajena = Explotacion::create(['propietario_id' => Propietario::create(['nombre' => 'Otro', 'apellidos' => 'Dueño'])->id, 'nombre' => 'Ajena']);

        $this->actingAs($this->admin())
            ->patch(route('propietarios.update', $propietario), $this->datos(
                [$this->direccion(['id' => $direccion->id])],
                [['id' => $ajena->id, 'nombre' => 'Robada']],
            ))
            ->assertSessionHasErrors('explotaciones.0.id');

        $this->assertDatabaseHas('explotaciones', ['id' => $ajena->id, 'nombre' => 'Ajena']);
    }
}
