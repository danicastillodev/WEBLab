<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\Explotacion;
use App\Models\FuncionZootecnica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El "Tipo de muestra" de cada análisis en el alta de una historia clínica
 * sale de los tipos de muestra elegidos para ese servicio en el catálogo de
 * análisis, no de los productos.
 */
class HistoriaClinicaTipoMuestraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    /** @return array<string, mixed> */
    private function datosHistoria(array $muestra): array
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
        $explotacion = Explotacion::create([
            'propietario_id' => $propietario->id, 'nombre' => 'Rancho Uno',
            'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Ave']);
        $raza = Raza::firstOrCreate(['nombre' => 'Leghorn', 'especie_id' => $especie->id]);
        $funcion = FuncionZootecnica::firstOrCreate(['nombre' => 'POSTURA']);
        $funcion->especies()->syncWithoutDetaching([$especie->id]);

        return [
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'explotacion_id' => $explotacion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'funcion_zootecnica_id' => $funcion->id,
            'fecha_recepcion' => now()->toDateString(),
            'fecha_muestra' => now()->toDateString(),
            'edad_unidad' => 'NA',
            'muestras' => [array_merge(['cantidad' => 1], $muestra)],
        ];
    }

    public function test_cada_servicio_ofrece_sus_tipos_de_muestra(): void
    {
        $suero = TipoMuestra::create(['nombre' => 'SUERO']);
        TipoMuestra::create(['nombre' => 'AGUA']);
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $servicio->tiposMuestra()->attach($suero);

        $respuesta = $this->actingAs($this->admin())
            ->get(route('historias-clinicas.create'))
            ->assertOk();

        $servicios = collect($respuesta->viewData('page')['props']['servicios']);
        $tipos = $servicios->firstWhere('id', $servicio->id)['tipos_muestra'];

        $this->assertSame(['SUERO'], array_column($tipos, 'nombre'));
    }

    public function test_guarda_el_tipo_de_muestra_del_servicio(): void
    {
        $suero = TipoMuestra::create(['nombre' => 'SUERO']);
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $servicio->tiposMuestra()->attach($suero);

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $this->datosHistoria([
                'servicio_id' => $servicio->id, 'tipo_muestra_id' => $suero->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('muestras', ['servicio_id' => $servicio->id, 'tipo_muestra_id' => $suero->id]);
    }

    public function test_propietario_y_direccion_son_obligatorios(): void
    {
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $datos = $this->datosHistoria(['servicio_id' => $servicio->id]);
        unset($datos['propietario_id'], $datos['direccion_id']);

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $datos)
            ->assertSessionHasErrors(['propietario_id', 'direccion_id']);

        $this->assertDatabaseCount('historias_clinicas', 0);
    }

    public function test_rechaza_una_funcion_zootecnica_que_no_es_de_la_especie(): void
    {
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $datos = $this->datosHistoria(['servicio_id' => $servicio->id]);
        $datos['funcion_zootecnica_id'] = FuncionZootecnica::where('nombre', 'LIDIA')->value('id');

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $datos)
            ->assertSessionHasErrors('funcion_zootecnica_id');

        $this->assertDatabaseCount('historias_clinicas', 0);
    }

    public function test_rechaza_un_tipo_de_muestra_que_no_es_del_servicio(): void
    {
        $suero = TipoMuestra::create(['nombre' => 'SUERO']);
        $agua = TipoMuestra::create(['nombre' => 'AGUA']);
        $servicio = Servicio::create(['clave' => 'COPRO', 'descripcion' => 'Coprológico', 'activo' => true]);
        $servicio->tiposMuestra()->attach($suero);

        $this->actingAs($this->admin())
            ->post(route('historias-clinicas.store'), $this->datosHistoria([
                'servicio_id' => $servicio->id, 'tipo_muestra_id' => $agua->id,
            ]))
            ->assertSessionHasErrors('muestras.0.tipo_muestra_id');

        $this->assertDatabaseCount('muestras', 0);
    }
}
