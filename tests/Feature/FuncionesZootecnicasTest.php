<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\FuncionZootecnica;
use App\Models\User;
use Database\Seeders\FuncionesZootecnicasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuncionesZootecnicasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_el_catalogo_es_el_del_sistema_anterior_enlazado_a_especies(): void
    {
        $this->assertSame(count(FuncionesZootecnicasSeeder::CATALOGO), FuncionZootecnica::count());
        $this->assertDatabaseHas('funcion_zootecnicas', ['id' => 81, 'nombre' => 'SEMENTAL']);

        $porcinos = Especie::where('nombre', 'Porcinos')->first();
        $aves = Especie::where('nombre', 'Aves')->first();
        $this->assertTrue($porcinos->funcionesZootecnicas()->where('nombre', 'LECHONERA')->exists());
        $this->assertFalse($aves->funcionesZootecnicas()->where('nombre', 'LECHONERA')->exists());
        $this->assertTrue($aves->funcionesZootecnicas()->where('nombre', 'POSTURA')->exists());

        // NR aplica a todas las especies.
        $nr = FuncionZootecnica::where('nombre', 'NR')->first();
        $this->assertSame(Especie::count(), $nr->especies()->count());
    }

    public function test_la_especie_guarda_sus_funciones(): void
    {
        $especie = Especie::where('nombre', 'Conejo')->first();
        $carne = FuncionZootecnica::where('nombre', 'CARNE')->value('id');
        $mascota = FuncionZootecnica::where('nombre', 'MASCOTA')->value('id');

        $this->actingAs($this->admin())
            ->get(route('especies.edit', $especie))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('funciones', FuncionZootecnica::count())->has('funcion_ids'));

        $this->actingAs($this->admin())
            ->patch(route('especies.update', $especie), ['nombre' => 'Conejo', 'funcion_ids' => [$carne, $mascota]])
            ->assertRedirect(route('especies.index'));

        $this->assertEqualsCanonicalizing([$carne, $mascota], $especie->funcionesZootecnicas()->pluck('funcion_zootecnicas.id')->all());
    }

    public function test_crea_una_funcion_para_varias_especies(): void
    {
        $especies = Especie::whereIn('nombre', ['Bovinos', 'Equinos'])->pluck('id')->all();

        $this->actingAs($this->admin())
            ->post(route('funcion-zootecnicas.store'), ['nombre' => 'TRABAJO', 'especie_ids' => $especies])
            ->assertRedirect(route('funcion-zootecnicas.index'));

        $funcion = FuncionZootecnica::where('nombre', 'TRABAJO')->firstOrFail();
        $this->assertEqualsCanonicalizing($especies, $funcion->especies()->pluck('especies.id')->all());

        $this->actingAs($this->admin())
            ->post(route('funcion-zootecnicas.store'), ['nombre' => 'TRABAJO'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_el_listado_filtra_y_muestra_las_especies(): void
    {
        $this->actingAs($this->admin())
            ->get(route('funcion-zootecnicas.index', ['buscar' => 'lechonera']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('funciones.data', 2)
                ->where('funciones.data.0.nombre', 'LECHONERA')
                ->where('funciones.data.0.especies.0.nombre', 'Porcinos'));
    }

    public function test_desde_la_historia_reutiliza_una_funcion_existente(): void
    {
        $conejo = Especie::where('nombre', 'Conejo')->value('id');

        $this->actingAs($this->admin())
            ->postJson(route('historias-clinicas.store-funcion-zootecnica'), ['nombre' => 'LIDIA', 'especie_id' => $conejo])
            ->assertOk()
            ->assertJsonPath('id', FuncionZootecnica::where('nombre', 'LIDIA')->value('id'));

        $this->assertSame(count(FuncionesZootecnicasSeeder::CATALOGO), FuncionZootecnica::count());
        $this->assertTrue(FuncionZootecnica::where('nombre', 'LIDIA')->first()->especies()->where('especies.id', $conejo)->exists());
    }
}
