<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogo de áreas y sus miembros. Un usuario puede pertenecer a varias
 * áreas, así que la relación vive en la tabla pivote area_user.
 */
class AreasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_el_listado_trae_cada_area_con_sus_miembros(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $ana = User::factory()->create(['name' => 'Ana Ruiz']);
        $area->users()->attach($ana);

        $this->actingAs($this->admin())
            ->get(route('areas.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('areas.0.nombre', 'Bacteriología')
                ->where('areas.0.users_count', 1)
                ->where('areas.0.users.0.name', 'Ana Ruiz')
                ->has('usuarios'));
    }

    public function test_agrega_un_usuario_al_area(): void
    {
        $area = Area::create(['nombre' => 'Parasitología']);
        $ana = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $area->id), ['user_id' => $ana->id])
            ->assertRedirect(route('areas.index'));

        $this->assertDatabaseHas('area_user', [
            'area_id' => $area->id,
            'user_id' => $ana->id,
        ]);
    }

    public function test_agregar_dos_veces_no_duplica_la_membresia(): void
    {
        $area = Area::create(['nombre' => 'Parasitología']);
        $ana = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $area->id), ['user_id' => $ana->id]);
        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $area->id), ['user_id' => $ana->id]);

        $this->assertSame(1, $area->users()->count());
    }

    public function test_un_usuario_puede_pertenecer_a_varias_areas(): void
    {
        $bacteriologia = Area::create(['nombre' => 'Bacteriología']);
        $parasitologia = Area::create(['nombre' => 'Parasitología']);
        $ana = User::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $bacteriologia->id), ['user_id' => $ana->id]);
        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $parasitologia->id), ['user_id' => $ana->id]);

        $this->assertEqualsCanonicalizing(
            [$bacteriologia->id, $parasitologia->id],
            $ana->areas()->pluck('areas.id')->all(),
        );
    }

    public function test_quita_un_usuario_del_area(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $ana = User::factory()->create();
        $area->users()->attach($ana);

        $this->actingAs($this->admin())
            ->delete(route('areas.usuarios.destroy', [$area->id, $ana->id]))
            ->assertRedirect(route('areas.index'));

        $this->assertDatabaseMissing('area_user', [
            'area_id' => $area->id,
            'user_id' => $ana->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $ana->id]);
    }

    public function test_no_agrega_un_usuario_inexistente(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);

        $this->actingAs($this->admin())
            ->post(route('areas.usuarios.store', $area->id), ['user_id' => 9999])
            ->assertSessionHasErrors('user_id');
    }

    public function test_al_eliminar_el_area_se_borran_sus_membresias_y_no_los_usuarios(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $ana = User::factory()->create();
        $area->users()->attach($ana);

        $this->actingAs($this->admin())
            ->delete(route('areas.destroy', $area->id))
            ->assertRedirect(route('areas.index'));

        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
        $this->assertDatabaseMissing('area_user', ['area_id' => $area->id]);
        $this->assertDatabaseHas('users', ['id' => $ana->id]);
    }
}
