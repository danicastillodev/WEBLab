<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Metodo;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_el_listado_trae_los_servicios_con_su_area(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $metodo = Metodo::create(['nombre' => 'Número Más Probable']);
        Servicio::create([
            'clave' => 'BTA24', 'siglas' => 'BAAG', 'descripcion' => 'BACTER. AGUAS',
            'metodo_id' => $metodo->id, 'area_id' => $area->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('servicios.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('servicios.data.0.clave', 'BTA24')
                ->where('servicios.data.0.area.nombre', 'Bacteriología')
                ->where('servicios.data.0.metodo.nombre', 'Número Más Probable'));
    }

    public function test_filtra_el_listado_por_area(): void
    {
        $bacteriologia = Area::create(['nombre' => 'Bacteriología']);
        $pcr = Area::create(['nombre' => 'PCR']);
        Servicio::create(['clave' => 'BTA24', 'descripcion' => 'BACTER. AGUAS', 'area_id' => $bacteriologia->id]);
        Servicio::create(['clave' => 'PCR01', 'descripcion' => 'PRRS PCR', 'area_id' => $pcr->id]);

        $this->actingAs($this->admin())
            ->get(route('servicios.index', ['area_id' => $pcr->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('servicios.data.0.clave', 'PCR01')
                ->has('servicios.data', 1));
    }

    public function test_crea_un_servicio(): void
    {
        $metodo = Metodo::create(['nombre' => 'PCR']);

        $this->actingAs($this->admin())
            ->post(route('servicios.store'), [
                'clave' => 'PCR11',
                'siglas' => 'VAPCR',
                'descripcion' => 'DETECCION VIRAL POR PCR',
                'metodo_id' => $metodo->id,
                'referencias' => 'PROCEDIMIENTO INTERNO',
                'activo' => true,
            ])
            ->assertRedirect(route('servicios.index'));

        $this->assertDatabaseHas('servicios', ['clave' => 'PCR11', 'metodo_id' => $metodo->id, 'activo' => true]);
    }

    public function test_la_clave_debe_ser_unica(): void
    {
        Servicio::create(['clave' => 'PCR11', 'descripcion' => 'Existente']);

        $this->actingAs($this->admin())
            ->post(route('servicios.store'), ['clave' => 'PCR11', 'descripcion' => 'Duplicado'])
            ->assertSessionHasErrors('clave');
    }

    public function test_actualiza_un_servicio(): void
    {
        $servicio = Servicio::create(['clave' => 'PCR11', 'descripcion' => 'DETECCION VIRAL POR PCR']);

        $this->actingAs($this->admin())
            ->patch(route('servicios.update', $servicio), [
                'clave' => 'PCR11',
                'descripcion' => 'DETECCION VIRAL POR PCR (actualizado)',
                'activo' => false,
            ])
            ->assertRedirect(route('servicios.index'));

        $this->assertDatabaseHas('servicios', ['id' => $servicio->id, 'activo' => false]);
    }

    public function test_elimina_un_servicio(): void
    {
        $servicio = Servicio::create(['clave' => 'PCR11', 'descripcion' => 'DETECCION VIRAL POR PCR']);

        $this->actingAs($this->admin())
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect(route('servicios.index'));

        $this->assertDatabaseMissing('servicios', ['id' => $servicio->id]);
    }
}
