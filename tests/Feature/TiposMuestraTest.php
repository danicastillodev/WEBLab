<?php

namespace Tests\Feature;

use App\Models\Prueba;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiposMuestraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_el_listado_solo_muestra_el_catalogo_general(): void
    {
        $prueba = Prueba::create(['nombre' => 'Brucela']);
        TipoMuestra::create(['nombre' => 'SUERO', 'prueba_id' => $prueba->id]);
        TipoMuestra::create(['nombre' => 'AGUA']);

        $this->actingAs($this->admin())
            ->get(route('tipos-muestra.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('tiposMuestra.data', 1)
                ->where('tiposMuestra.data.0.nombre', 'AGUA'));
    }

    public function test_crea_un_tipo_de_muestra_en_mayusculas(): void
    {
        $this->actingAs($this->admin())
            ->post(route('tipos-muestra.store'), ['nombre' => ' absceso '])
            ->assertRedirect(route('tipos-muestra.index'));

        $this->assertDatabaseHas('tipo_muestras', ['nombre' => 'ABSCESO', 'prueba_id' => null]);
    }

    public function test_el_nombre_debe_ser_unico_en_el_catalogo_general(): void
    {
        $prueba = Prueba::create(['nombre' => 'Brucela']);
        TipoMuestra::create(['nombre' => 'SUERO', 'prueba_id' => $prueba->id]);
        TipoMuestra::create(['nombre' => 'AGUA']);

        $this->actingAs($this->admin());

        $this->post(route('tipos-muestra.store'), ['nombre' => 'agua'])->assertSessionHasErrors('nombre');
        // Un nombre usado sólo por un registro histórico de prueba sí se permite.
        $this->post(route('tipos-muestra.store'), ['nombre' => 'suero'])->assertSessionHasNoErrors();
    }

    public function test_actualiza_y_elimina(): void
    {
        $tipo = TipoMuestra::create(['nombre' => 'AGUA']);

        $this->actingAs($this->admin());

        $this->patch(route('tipos-muestra.update', $tipo), ['nombre' => 'Agua potable'])
            ->assertRedirect(route('tipos-muestra.index'));
        $this->assertSame('AGUA POTABLE', $tipo->fresh()->nombre);

        $this->delete(route('tipos-muestra.destroy', $tipo))->assertRedirect(route('tipos-muestra.index'));
        $this->assertModelMissing($tipo);
    }

    public function test_no_administra_tipos_de_muestra_ligados_a_una_prueba(): void
    {
        $prueba = Prueba::create(['nombre' => 'Brucela']);
        $legado = TipoMuestra::create(['nombre' => 'SUERO', 'prueba_id' => $prueba->id]);

        $this->actingAs($this->admin());

        $this->get(route('tipos-muestra.edit', $legado))->assertNotFound();
        $this->delete(route('tipos-muestra.destroy', $legado))->assertNotFound();
        $this->assertModelExists($legado);
    }

    public function test_requiere_permiso(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('tipos-muestra.index'))
            ->assertForbidden();
    }
}
