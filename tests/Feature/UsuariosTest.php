<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Alta y edición de usuarios con su control de acceso, ya sin los roles de
 * Spatie: la bandera es_admin, la ventana horaria y la matriz de permisos.
 */
class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    /** @return array<string, mixed> */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'name' => 'Laura Méndez',
            'username' => 'lmendez',
            'email' => 'lmendez@example.com',
            'area_ids' => [],
            'password' => 'contrasena-larga-1',
            'password_confirmation' => 'contrasena-larga-1',
            'es_admin' => false,
            'hora_inicio' => '',
            'hora_fin' => '',
            'dias_permitidos' => [],
            'permisos' => [],
            'permisos_granulares' => [],
        ], $extra);
    }

    public function test_el_listado_muestra_los_usuarios_con_sus_areas(): void
    {
        $bacteriologia = Area::create(['nombre' => 'Bacteriología']);
        $parasitologia = Area::create(['nombre' => 'Parasitología']);

        $ana = User::factory()->create(['name' => 'Ana Ruiz']);
        $ana->areas()->sync([$bacteriologia->id, $parasitologia->id]);

        $this->actingAs($this->admin())
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(function ($page) use ($ana) {
                // El listado va ordenado por nombre y paginado, así que se busca
                // la fila del usuario en vez de asumir que es la primera.
                $fila = collect($page->toArray()['props']['users']['data'])
                    ->firstWhere('id', $ana->id);

                $this->assertNotNull($fila);
                $this->assertEqualsCanonicalizing(
                    ['Bacteriología', 'Parasitología'],
                    array_column($fila['areas'], 'nombre'),
                );
            });
    }

    public function test_el_formulario_de_alta_ofrece_la_matriz_de_permisos(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('areas')
                    ->where('modulos.historias-clinicas', 'Historias Clínicas')
                    ->where('acciones.ver', 'Ver')
                    ->has('dias', 7)
                    ->where('gruposPermisos.facturacion.etiqueta', 'Facturación');

                $this->assertSame(
                    'Agregar facturas',
                    $page->toArray()['props']['gruposPermisos']['facturacion']['permisos']['facturacion.agregar_facturas'],
                );
            });
    }

    public function test_crea_un_usuario_con_permisos_y_horario(): void
    {
        $bacteriologia = Area::create(['nombre' => 'Bacteriología']);
        $parasitologia = Area::create(['nombre' => 'Parasitología']);

        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->datos([
                'area_ids' => [$bacteriologia->id, $parasitologia->id],
                'hora_inicio' => '08:00',
                'hora_fin' => '17:30',
                'dias_permitidos' => [1, 2, 3, 4, 5],
                'permisos' => [
                    'historias-clinicas' => ['ver' => true, 'crear' => true, 'editar' => false, 'eliminar' => false],
                    'estados' => ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false],
                ],
            ]))
            ->assertRedirect(route('users.index'));

        $user = User::where('username', 'lmendez')->firstOrFail();

        $this->assertFalse((bool) $user->es_admin);
        $this->assertEqualsCanonicalizing(
            [$bacteriologia->id, $parasitologia->id],
            $user->areas()->pluck('areas.id')->all(),
        );
        $this->assertSame([1, 2, 3, 4, 5], $user->dias_permitidos);
        $this->assertTrue(Hash::check('contrasena-larga-1', $user->password));

        // Sólo se guardan las filas con alguna casilla marcada.
        $this->assertSame(2, $user->permissions()->count());
        $this->assertTrue($user->puede('historias-clinicas', 'crear'));
        $this->assertFalse($user->puede('historias-clinicas', 'eliminar'));
        $this->assertFalse($user->puede('pruebas', 'ver'));
    }

    public function test_crea_un_usuario_con_permisos_granulares(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->datos([
                'permisos_granulares' => [
                    'facturacion.agregar_facturas' => true,
                    'reportes.global' => true,
                    'diagnosticos.emitir' => false,
                ],
            ]))
            ->assertRedirect(route('users.index'));

        $user = User::where('username', 'lmendez')->firstOrFail();

        $this->assertTrue($user->tienePermisoGranular('facturacion.agregar_facturas'));
        $this->assertTrue($user->tienePermisoGranular('reportes.global'));
        $this->assertFalse($user->tienePermisoGranular('diagnosticos.emitir'));
        $this->assertSame(2, $user->permisosGranulares()->count());
    }

    public function test_editar_reemplaza_los_permisos_granulares(): void
    {
        $user = User::factory()->create(['username' => 'jperez']);
        $user->permisosGranulares()->create(['clave' => 'reportes.global']);

        $this->actingAs($this->admin())
            ->patch(route('users.update', $user->id), $this->datos([
                'name' => $user->name,
                'username' => 'jperez',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'permisos_granulares' => [
                    'facturacion.cancelar_factura' => true,
                ],
            ]))
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertFalse($user->tienePermisoGranular('reportes.global'));
        $this->assertTrue($user->tienePermisoGranular('facturacion.cancelar_factura'));
        $this->assertSame(1, $user->permisosGranulares()->count());
    }

    public function test_el_formulario_de_edicion_precarga_los_permisos_granulares_guardados(): void
    {
        $user = User::factory()->create();
        $user->permisosGranulares()->create(['clave' => 'historias-clinicas.imprimir_resultado']);

        $this->actingAs($this->admin())
            ->get(route('users.edit', $user->id))
            ->assertOk()
            ->assertInertia(function ($page) {
                $permisos = $page->toArray()['props']['user']['permisos_granulares'];

                $this->assertTrue($permisos['historias-clinicas.imprimir_resultado']);
                $this->assertFalse($permisos['historias-clinicas.reimprimir_resultado']);
            });
    }

    public function test_el_username_debe_ser_unico(): void
    {
        User::factory()->create(['username' => 'lmendez']);

        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->datos())
            ->assertSessionHasErrors('username');
    }

    public function test_editar_reemplaza_la_matriz_de_permisos(): void
    {
        $user = User::factory()->create(['username' => 'jperez']);
        $user->permissions()->create(['modulo' => 'pruebas', 'ver' => true, 'crear' => true]);

        $this->actingAs($this->admin())
            ->patch(route('users.update', $user->id), $this->datos([
                'name' => $user->name,
                'username' => 'jperez',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'permisos' => [
                    'estados' => ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false],
                ],
            ]))
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertFalse($user->puede('pruebas', 'ver'));
        $this->assertTrue($user->puede('estados', 'ver'));
        $this->assertSame(1, $user->permissions()->count());
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $user = User::factory()->create([
            'username' => 'jperez',
            'password' => Hash::make('la-de-siempre-1'),
        ]);

        $this->actingAs($this->admin())
            ->patch(route('users.update', $user->id), $this->datos([
                'name' => $user->name,
                'username' => 'jperez',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('la-de-siempre-1', $user->refresh()->password));
    }

    public function test_el_formulario_de_edicion_precarga_los_permisos_guardados(): void
    {
        $user = User::factory()->create(['hora_inicio' => '08:00:00', 'hora_fin' => '17:30:00']);
        $user->permissions()->create(['modulo' => 'razas', 'ver' => true]);

        $this->actingAs($this->admin())
            ->get(route('users.edit', $user->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // El input type="time" espera HH:MM, no HH:MM:SS.
                ->where('user.hora_inicio', '08:00')
                ->where('user.hora_fin', '17:30')
                ->where('user.permisos.razas.ver', true)
                ->where('user.permisos.razas.crear', false)
                ->where('user.permisos.estados.ver', false));
    }

    public function test_una_hora_sin_su_pareja_es_invalida(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), $this->datos(['hora_inicio' => '08:00']))
            ->assertSessionHasErrors('hora_fin');
    }

    public function test_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin->id))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_elimina_a_otro_usuario_con_sus_permisos(): void
    {
        $otro = User::factory()->create();
        $otro->permissions()->create(['modulo' => 'razas', 'ver' => true]);

        $this->actingAs($this->admin())
            ->delete(route('users.destroy', $otro->id))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $otro->id]);
        $this->assertDatabaseMissing('user_permissions', ['user_id' => $otro->id]);
    }
}
