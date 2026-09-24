<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Prueba;
use App\Models\Raza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edición y eliminación en línea de catálogos desde el formulario de alta de
 * historias clínicas. Lo crítico aquí son los candados de eliminación: varias
 * llaves foráneas son CASCADE y borrar sin protección arrastraría historias
 * clínicas completas.
 */
class CatalogoEnLineaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    private function historiaCompleta(): HistoriaClinica
    {
        $propietario = Propietario::create([
            'nombre' => 'Ana', 'apellidos' => 'Pérez',
            'curp' => 'PEAA900101MJCRNN01', 'rfc' => 'PEAA900101AA1',
        ]);
        // Los estados y municipios ya vienen sembrados por sus migraciones.
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id, 'calle' => 'Av. Central',
            'numero_exterior' => '10', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id,
            'codigo_postal' => '44100',
        ]);
        $especie = Especie::create(['nombre' => 'Bovino']);
        $raza = Raza::create(['nombre' => 'Holstein', 'especie_id' => $especie->id]);

        return HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => now()->toDateString(),
            'edad_unidad' => 'NA',
        ]);
    }

    public function test_no_permite_eliminar_un_propietario_con_historias_clinicas(): void
    {
        $historia = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->deleteJson(route('historias-clinicas.destroy-propietario', $historia->propietario_id))
            ->assertStatus(409);

        $this->assertDatabaseHas('propietarios', ['id' => $historia->propietario_id]);
        $this->assertDatabaseHas('historias_clinicas', ['id' => $historia->id]);
    }

    public function test_no_permite_eliminar_una_especie_en_uso(): void
    {
        $historia = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->deleteJson(route('historias-clinicas.destroy-especie', $historia->especie_id))
            ->assertStatus(409);

        $this->assertDatabaseHas('especies', ['id' => $historia->especie_id]);
    }

    public function test_no_permite_eliminar_una_raza_en_uso(): void
    {
        $historia = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->deleteJson(route('historias-clinicas.destroy-raza', $historia->raza_id))
            ->assertStatus(409);

        $this->assertDatabaseHas('razas', ['id' => $historia->raza_id]);
    }

    public function test_elimina_un_registro_sin_dependencias(): void
    {
        $especie = Especie::create(['nombre' => 'Equino']);
        $prueba = Prueba::create(['nombre' => 'Hemograma', 'especie_id' => $especie->id]);

        $this->actingAs($this->admin())
            ->deleteJson(route('historias-clinicas.destroy-prueba', $prueba->id))
            ->assertOk();

        $this->assertDatabaseMissing('pruebas', ['id' => $prueba->id]);
    }

    /**
     * El formulario precarga el modal con los datos que viajan en la prop
     * `propietarios`; si el select del controlador omite CURP/RFC llegan vacíos
     * y el PATCH revienta por validación.
     */
    public function test_edita_un_propietario_en_linea_con_telefono(): void
    {
        $historia = $this->historiaCompleta();
        $propietario = Propietario::find($historia->propietario_id);

        $this->actingAs($this->admin())
            ->getJson(route('historias-clinicas.create'))
            ->assertOk();

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-propietario', $propietario->id), [
                'nombre' => $propietario->nombre,
                'apellidos' => $propietario->apellidos,
                'curp' => $propietario->curp,
                'rfc' => $propietario->rfc,
                'telefono' => '3312345678',
            ])
            ->assertOk()
            ->assertJsonPath('telefono', '3312345678');

        $this->assertDatabaseHas('propietarios', [
            'id' => $propietario->id, 'telefono' => '3312345678',
        ]);
    }

    public function test_rechaza_un_telefono_que_no_tiene_10_digitos(): void
    {
        $historia = $this->historiaCompleta();
        $prop = Propietario::find($historia->propietario_id);

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-propietario', $prop->id), [
                'nombre' => $prop->nombre, 'apellidos' => $prop->apellidos,
                'curp' => $prop->curp, 'rfc' => $prop->rfc,
                'telefono' => '123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('telefono');
    }

    public function test_acepta_un_propietario_sin_telefono(): void
    {
        $historia = $this->historiaCompleta();
        $prop = Propietario::find($historia->propietario_id);

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-propietario', $prop->id), [
                'nombre' => $prop->nombre, 'apellidos' => $prop->apellidos,
                'curp' => $prop->curp, 'rfc' => $prop->rfc,
                'telefono' => '',
            ])
            ->assertOk();
    }

    public function test_guarda_caseta_lote_y_parvada_en_la_direccion(): void
    {
        $historia = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-direccion', $historia->direccion_id), [
                'calle' => 'Camino a la granja', 'numero_exterior' => 'S/N',
                'colonia' => 'Rancho', 'estado_id' => Estado::where('clave', 'JAL')->value('id'),
                'municipio_id' => Municipio::value('id'), 'codigo_postal' => '45000',
                'caseta' => 'A-12', 'lote' => '7', 'parvada' => 'Norte 2',
            ])
            ->assertOk()
            ->assertJsonPath('caseta', 'A-12')
            ->assertJsonPath('lote', '7')
            ->assertJsonPath('parvada', 'Norte 2');

        $this->assertDatabaseHas('direcciones', [
            'id' => $historia->direccion_id,
            'caseta' => 'A-12', 'lote' => '7', 'parvada' => 'Norte 2',
        ]);
    }

    public function test_la_direccion_no_requiere_caseta_lote_ni_parvada(): void
    {
        $historia = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-direccion', $historia->direccion_id), [
                'calle' => 'Av. Central', 'numero_exterior' => '10',
                'colonia' => 'Centro', 'estado_id' => Estado::where('clave', 'JAL')->value('id'),
                'municipio_id' => Municipio::value('id'), 'codigo_postal' => '44100',
            ])
            ->assertOk();
    }

    public function test_crea_un_propietario_sin_curp_ni_rfc(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('historias-clinicas.store-propietario'), [
                'nombre' => 'Luis', 'apellidos' => 'Gómez',
            ])
            ->assertOk()
            ->assertJsonPath('curp', null)
            ->assertJsonPath('rfc', null);

        $this->assertDatabaseHas('propietarios', [
            'apellidos' => 'Gómez', 'curp' => null, 'rfc' => null,
        ]);
    }

    /**
     * El índice UNIQUE sigue vivo: si CURP/RFC se guardaran como cadena vacía
     * en lugar de NULL, el segundo propietario chocaría contra el índice.
     */
    public function test_permite_varios_propietarios_sin_curp_ni_rfc(): void
    {
        foreach ([['Ana', 'Ruiz'], ['Beto', 'Lara'], ['Carla', 'Mora']] as [$nombre, $apellidos]) {
            $this->actingAs($this->admin())
                ->postJson(route('historias-clinicas.store-propietario'), [
                    'nombre' => $nombre, 'apellidos' => $apellidos,
                    'curp' => '', 'rfc' => '',
                ])
                ->assertOk();
        }

        $this->assertSame(3, Propietario::whereNull('curp')->whereNull('rfc')->count());
    }

    public function test_sigue_validando_el_formato_cuando_si_se_captura_curp(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('historias-clinicas.store-propietario'), [
                'nombre' => 'Ema', 'apellidos' => 'Solís', 'curp' => 'MUY-CORTA',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('curp');
    }

    public function test_una_historia_nueva_nace_pendiente(): void
    {
        $historia = $this->historiaCompleta();

        $this->assertSame(HistoriaClinica::ESTADO_PENDIENTE, $historia->fresh()->estado);
    }

    /**
     * El estado lo mueve el sistema, no el formulario: aunque venga en el
     * payload debe ignorarse y quedar "pendiente".
     */
    public function test_el_formulario_no_puede_fijar_el_estado(): void
    {
        $base = $this->historiaCompleta();

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), [
                'propietario_id' => $base->propietario_id,
                'direccion_id' => $base->direccion_id,
                'especie_id' => $base->especie_id,
                'raza_id' => $base->raza_id,
                'fecha_recepcion' => now()->toDateString(),
                'edad_unidad' => 'NA',
                'estado' => HistoriaClinica::ESTADO_CONCLUIDA,
                'muestras' => [[
                    'prueba_id' => Prueba::create(['nombre' => 'Coprológico', 'especie_id' => $base->especie_id])->id,
                    'cantidad' => 1,
                ]],
            ])
            ->assertRedirect();

        $this->assertSame(
            HistoriaClinica::ESTADO_PENDIENTE,
            HistoriaClinica::latest('id')->first()->estado,
        );
    }

    public function test_edita_un_registro_en_linea(): void
    {
        $especie = Especie::create(['nombre' => 'Canino']);

        $this->actingAs($this->admin())
            ->patchJson(route('historias-clinicas.update-especie', $especie->id), ['nombre' => 'Felino'])
            ->assertOk()
            ->assertJsonPath('nombre', 'Felino');

        $this->assertDatabaseHas('especies', ['id' => $especie->id, 'nombre' => 'Felino']);
    }
}
