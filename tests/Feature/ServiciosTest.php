<?php

namespace Tests\Feature;

use App\Models\Analista;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Metodo;
use App\Models\Norma;
use App\Models\Producto;
use App\Models\Prueba;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_desactiva_y_reactiva_un_servicio_en_lugar_de_eliminarlo(): void
    {
        $servicio = Servicio::create(['clave' => 'PCR11', 'descripcion' => 'DETECCION VIRAL POR PCR']);

        $this->actingAs($this->admin());

        $this->patch(route('servicios.update-activo', $servicio))->assertSessionHas('success', 'Análisis desactivado.');
        $this->assertFalse($servicio->fresh()->activo);

        $this->patch(route('servicios.update-activo', $servicio))->assertSessionHas('success', 'Análisis activado.');
        $this->assertTrue($servicio->fresh()->activo);

        $this->assertFalse(Route::has('servicios.destroy'));
    }

    public function test_al_desactivar_un_analisis_se_desactivan_sus_productos(): void
    {
        $servicio = Servicio::create(['clave' => 'PCR11', 'descripcion' => 'DETECCION VIRAL POR PCR']);
        $otro = Servicio::create(['clave' => 'COP01', 'descripcion' => 'COPROLOGICO']);
        $pcr = Producto::create(['nombre' => 'PCR', 'servicio_id' => $servicio->id, 'precio_unitario' => 50, 'activo' => true]);
        $pcrUrgente = Producto::create(['nombre' => 'PCR URGENTE', 'servicio_id' => $servicio->id, 'precio_unitario' => 90, 'activo' => true]);
        $copro = Producto::create(['nombre' => 'COPRO', 'servicio_id' => $otro->id, 'precio_unitario' => 30, 'activo' => true]);

        $this->actingAs($this->admin())->patch(route('servicios.update-activo', $servicio));

        $this->assertFalse($pcr->fresh()->activo);
        $this->assertFalse($pcrUrgente->fresh()->activo);
        $this->assertTrue($copro->fresh()->activo);

        $this->patch(route('servicios.update-activo', $servicio));

        $this->assertTrue($servicio->fresh()->activo);
        $this->assertFalse($pcr->fresh()->activo);
    }

    public function test_desactivar_requiere_permiso_de_editar(): void
    {
        $servicio = Servicio::create(['clave' => 'PCR11', 'descripcion' => 'X']);

        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->patch(route('servicios.update-activo', $servicio))
            ->assertForbidden();

        $this->assertTrue($servicio->fresh()->activo);
    }

    public function test_el_alta_muestra_el_siguiente_numero_consecutivo(): void
    {
        Servicio::create(['clave' => 'A1', 'descripcion' => 'Uno']);
        Servicio::create(['clave' => 'A2', 'descripcion' => 'Dos']);

        $this->actingAs($this->admin())
            ->get(route('servicios.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('siguienteNumero', 3)
                ->has('categorias')->has('analistas')->has('normas')->has('tiposMuestra'));
    }

    public function test_el_numero_se_asigna_en_orden_al_crear(): void
    {
        $this->actingAs($this->admin());
        Servicio::create(['clave' => 'A1', 'descripcion' => 'Uno']);

        $this->post(route('servicios.store'), ['clave' => 'A2', 'descripcion' => 'Dos'])
            ->assertRedirect(route('servicios.index'));

        $this->assertSame(2, Servicio::where('clave', 'A2')->value('numero'));
    }

    public function test_crea_un_servicio_con_todos_los_campos_del_alta(): void
    {
        $categoria = Categoria::create(['nombre' => 'GENERAL']);
        $analista = Analista::create(['nombre' => 'CENASA']);
        $norma = Norma::create(['nombre' => 'EMA BACTER TB']);
        $tipo = TipoMuestra::create(['nombre' => 'AGUA']);

        $this->actingAs($this->admin())
            ->post(route('servicios.store'), [
                'clave' => 'BTA24',
                'descripcion' => 'BACTER. AGUAS',
                'nombre_analisis' => 'Bacteriología de agua',
                'categoria_id' => $categoria->id,
                'analista_id' => $analista->id,
                'normas' => [$norma->id],
                'tipos_muestra' => [$tipo->id],
                'compuesto' => true,
                'independiente' => true,
                'activo' => true,
            ])
            ->assertRedirect(route('servicios.index'));

        $servicio = Servicio::where('clave', 'BTA24')->firstOrFail();
        $this->assertSame('Bacteriología de agua', $servicio->nombre_analisis);
        $this->assertSame($categoria->id, $servicio->categoria_id);
        $this->assertSame($analista->id, $servicio->analista_id);
        $this->assertTrue($servicio->compuesto);
        $this->assertTrue($servicio->independiente);
        $this->assertEquals([$norma->id], $servicio->normas->pluck('id')->all());
        $this->assertEquals([$tipo->id], $servicio->tiposMuestra->pluck('id')->all());
    }

    public function test_al_editar_se_sincronizan_normas_y_tipos_de_muestra(): void
    {
        $servicio = Servicio::create(['clave' => 'BTA24', 'descripcion' => 'BACTER. AGUAS']);
        [$n1, $n2] = [Norma::create(['nombre' => 'N1']), Norma::create(['nombre' => 'N2'])];
        $servicio->normas()->attach($n1);

        $this->actingAs($this->admin())
            ->patch(route('servicios.update', $servicio), [
                'clave' => 'BTA24', 'descripcion' => 'BACTER. AGUAS', 'normas' => [$n2->id],
            ])
            ->assertRedirect(route('servicios.index'));

        $this->assertEquals([$n2->id], $servicio->fresh()->normas->pluck('id')->all());
    }

    public function test_no_acepta_tipos_de_muestra_ligados_a_una_prueba(): void
    {
        $prueba = Prueba::create(['nombre' => 'Brucela']);
        $legado = TipoMuestra::create(['nombre' => 'Suero', 'prueba_id' => $prueba->id]);

        $this->actingAs($this->admin())
            ->post(route('servicios.store'), ['clave' => 'X', 'descripcion' => 'X', 'tipos_muestra' => [$legado->id]])
            ->assertSessionHasErrors('tipos_muestra.0');
    }

    public function test_alta_rapida_de_catalogos_desde_el_formulario(): void
    {
        $this->actingAs($this->admin());

        foreach (['metodo' => 'metodos', 'categoria' => 'categorias', 'tipo-muestra' => 'tipo_muestras',
            'area' => 'areas', 'analista' => 'analistas', 'norma' => 'normas'] as $catalogo => $tabla) {
            $this->postJson(route('servicios.store-catalogo', $catalogo), ['nombre' => 'nuevo '.$catalogo])
                ->assertCreated()
                ->assertJsonPath('nombre', mb_strtoupper('nuevo '.$catalogo));

            $this->assertDatabaseHas($tabla, ['nombre' => mb_strtoupper('nuevo '.$catalogo)]);
        }

        $this->postJson(route('servicios.store-catalogo', 'categoria'), ['nombre' => 'Nuevo categoria'])
            ->assertUnprocessable();
        $this->postJson(route('servicios.store-catalogo', 'inexistente'), ['nombre' => 'X'])
            ->assertNotFound();
    }

    public function test_alta_rapida_requiere_permiso_de_crear_servicios(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->postJson(route('servicios.store-catalogo', 'norma'), ['nombre' => 'X'])
            ->assertForbidden();
    }

    public function test_modifica_un_registro_de_catalogo_desde_el_formulario(): void
    {
        $categoria = Categoria::create(['nombre' => 'GENERAL']);
        Categoria::create(['nombre' => 'OTRA']);

        $this->actingAs($this->admin());

        $this->patchJson(route('servicios.update-catalogo', ['categoria', $categoria->id]), ['nombre' => 'general 2'])
            ->assertOk()
            ->assertJsonPath('nombre', 'GENERAL 2');
        $this->assertSame('GENERAL 2', $categoria->fresh()->nombre);

        $this->patchJson(route('servicios.update-catalogo', ['categoria', $categoria->id]), ['nombre' => 'otra'])
            ->assertUnprocessable();
    }

    public function test_elimina_un_registro_de_catalogo_no_asignado(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            'metodo' => Metodo::create(['nombre' => 'PCR']),
            'categoria' => Categoria::create(['nombre' => 'GENERAL']),
            'area' => Area::create(['nombre' => 'PCR']),
            'analista' => Analista::create(['nombre' => 'CENASA']),
        ] as $catalogo => $registro) {
            $this->deleteJson(route('servicios.destroy-catalogo', [$catalogo, $registro->id]))->assertOk();
            $this->assertModelMissing($registro);
        }
    }

    public function test_no_elimina_un_registro_de_catalogo_asignado_a_un_analisis(): void
    {
        $metodo = Metodo::create(['nombre' => 'PCR']);
        $categoria = Categoria::create(['nombre' => 'GENERAL']);
        $area = Area::create(['nombre' => 'PCR']);
        $analista = Analista::create(['nombre' => 'CENASA']);
        Servicio::create([
            'clave' => 'PCR11', 'descripcion' => 'X', 'metodo_id' => $metodo->id,
            'categoria_id' => $categoria->id, 'area_id' => $area->id, 'analista_id' => $analista->id,
        ]);

        $this->actingAs($this->admin());

        foreach (['metodo' => $metodo, 'categoria' => $categoria, 'area' => $area, 'analista' => $analista] as $catalogo => $registro) {
            $this->deleteJson(route('servicios.destroy-catalogo', [$catalogo, $registro->id]))
                ->assertStatus(409)
                ->assertJsonPath('message', "No se puede eliminar \"{$registro->nombre}\": está asignado a 1 análisis.");
            $this->assertModelExists($registro);
        }
    }

    public function test_solo_metodo_categoria_area_y_analista_se_modifican_o_eliminan(): void
    {
        $norma = Norma::create(['nombre' => 'N1']);

        $this->actingAs($this->admin());

        $this->deleteJson(route('servicios.destroy-catalogo', ['norma', $norma->id]))->assertNotFound();
        $this->patchJson(route('servicios.update-catalogo', ['norma', $norma->id]), ['nombre' => 'X'])->assertNotFound();
    }

    public function test_modificar_y_eliminar_catalogos_requiere_permiso(): void
    {
        $categoria = Categoria::create(['nombre' => 'GENERAL']);

        $this->actingAs(User::factory()->create(['es_admin' => false]));

        $this->patchJson(route('servicios.update-catalogo', ['categoria', $categoria->id]), ['nombre' => 'X'])->assertForbidden();
        $this->deleteJson(route('servicios.destroy-catalogo', ['categoria', $categoria->id]))->assertForbidden();
        $this->assertModelExists($categoria);
    }

    public function test_el_listado_muestra_todos_por_defecto_y_filtra_por_estado(): void
    {
        Servicio::create(['clave' => 'ACT', 'descripcion' => 'Activo']);
        Servicio::create(['clave' => 'INA', 'descripcion' => 'Inactivo', 'activo' => false]);

        $this->actingAs($this->admin());

        $this->get(route('servicios.index'))
            ->assertInertia(fn ($page) => $page
                ->where('filtros.estado', 'todos')
                ->has('servicios.data', 2));

        $this->get(route('servicios.index', ['estado' => 'activos']))
            ->assertInertia(fn ($page) => $page->has('servicios.data', 1)->where('servicios.data.0.clave', 'ACT'));

        $this->get(route('servicios.index', ['estado' => 'inactivos']))
            ->assertInertia(fn ($page) => $page->has('servicios.data', 1)->where('servicios.data.0.clave', 'INA'));

    }
}
