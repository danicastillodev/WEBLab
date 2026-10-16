<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roles como plantillas de permisos: los cuatro predefinidos, su
 * mantenimiento desde Seguridad > Roles y su asignación a usuarios.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_existen_los_cuatro_roles_predefinidos(): void
    {
        $this->assertEqualsCanonicalizing(
            ['Recepción', 'Análisis', 'Auxiliar', 'Dirección'],
            Rol::pluck('nombre')->all(),
        );

        $recepcion = Rol::where('nombre', 'Recepción')->firstOrFail()->plantilla();
        $this->assertTrue($recepcion['permisos']['historias-clinicas']['crear']);
        $this->assertFalse($recepcion['permisos']['users']['ver']);
        $this->assertTrue($recepcion['permisos_granulares']['facturacion.agregar_facturas']);

        $direccion = Rol::where('nombre', 'Dirección')->firstOrFail()->plantilla();
        $this->assertTrue($direccion['permisos']['estados']['eliminar']);
        $this->assertTrue($direccion['permisos_granulares']['reportes.global']);
    }

    public function test_el_listado_muestra_los_roles_con_su_numero_de_usuarios(): void
    {
        $auxiliar = Rol::where('nombre', 'Auxiliar')->firstOrFail();
        User::factory()->count(2)->create(['rol_id' => $auxiliar->id]);

        $this->actingAs($this->admin())
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(function ($page) use ($auxiliar) {
                $fila = collect($page->toArray()['props']['roles'])->firstWhere('id', $auxiliar->id);
                $this->assertSame(2, $fila['users_count']);
            });
    }

    public function test_crea_un_rol_guardando_solo_lo_concedido(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'nombre' => 'Calidad',
                'descripcion' => 'Revisión de resultados',
                'permisos' => [
                    'estados' => ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false],
                    'razas' => ['ver' => false, 'crear' => false, 'editar' => false, 'eliminar' => false],
                    'inexistente' => ['ver' => true],
                ],
                'permisos_granulares' => [
                    'reportes.global' => true,
                    'diagnosticos.emitir' => false,
                ],
            ])
            ->assertRedirect(route('roles.index'));

        $rol = Rol::where('nombre', 'Calidad')->firstOrFail();

        $this->assertSame(['estados'], array_keys($rol->permisos));
        $this->assertSame(['reportes.global'], $rol->permisos_granulares);
    }

    public function test_el_nombre_del_rol_es_unico(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), ['nombre' => 'Recepción'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_edita_un_rol(): void
    {
        $rol = Rol::where('nombre', 'Auxiliar')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('roles.edit', $rol))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('rol.nombre', 'Auxiliar')
                ->where('rol.permisos.historias-clinicas.ver', true)
                ->where('rol.permisos.historias-clinicas.crear', false));

        $this->actingAs($this->admin())
            ->patch(route('roles.update', $rol), [
                'nombre' => 'Auxiliar de laboratorio',
                'permisos' => [
                    'pruebas' => ['ver' => true, 'crear' => true, 'editar' => false, 'eliminar' => false],
                ],
            ])
            ->assertRedirect(route('roles.index'));

        $rol->refresh();
        $this->assertSame('Auxiliar de laboratorio', $rol->nombre);
        $this->assertSame(['pruebas'], array_keys($rol->permisos));
    }

    public function test_eliminar_un_rol_no_quita_permisos_a_sus_usuarios(): void
    {
        $rol = Rol::where('nombre', 'Recepción')->firstOrFail();
        $usuario = User::factory()->create(['rol_id' => $rol->id]);
        $usuario->permissions()->create(['modulo' => 'estados', 'ver' => true]);

        $this->actingAs($this->admin())
            ->delete(route('roles.destroy', $rol))
            ->assertRedirect(route('roles.index'));

        $this->assertModelMissing($rol);
        $usuario->refresh();
        $this->assertNull($usuario->rol_id);
        $this->assertTrue($usuario->puede('estados', 'ver'));
    }

    public function test_el_rol_guarda_horario_y_dias_permitidos(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), [
                'nombre' => 'Turno matutino',
                'hora_inicio' => '07:00',
                'hora_fin' => '15:00',
                'dias_permitidos' => [5, 1, 3],
            ])
            ->assertRedirect(route('roles.index'));

        $rol = Rol::where('nombre', 'Turno matutino')->firstOrFail();
        $plantilla = $rol->plantilla();

        $this->assertSame('07:00', $plantilla['hora_inicio']);
        $this->assertSame('15:00', $plantilla['hora_fin']);
        $this->assertSame([1, 3, 5], $plantilla['dias_permitidos']);

        // El formulario de usuarios recibe el horario junto con la plantilla.
        $this->actingAs($this->admin())
            ->get(route('users.create'))
            ->assertInertia(function ($page) use ($rol) {
                $fila = collect($page->toArray()['props']['roles'])->firstWhere('id', $rol->id);
                $this->assertSame('07:00', $fila['hora_inicio']);
                $this->assertSame([1, 3, 5], $fila['dias_permitidos']);
            });
    }

    public function test_una_hora_del_rol_sin_su_pareja_es_invalida(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), ['nombre' => 'Incompleto', 'hora_inicio' => '07:00'])
            ->assertSessionHasErrors('hora_fin');
    }

    public function test_sin_permiso_no_se_accede_a_roles(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_el_formulario_de_usuarios_ofrece_los_roles_como_plantilla(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $roles = collect($page->toArray()['props']['roles']);
                $analisis = $roles->firstWhere('nombre', 'Análisis');

                $this->assertCount(4, $roles);
                $this->assertTrue($analisis['permisos']['historias-clinicas']['editar']);
                $this->assertTrue($analisis['permisos_granulares']['diagnosticos.emitir']);
            });
    }

    public function test_guarda_el_rol_asignado_al_usuario(): void
    {
        $rol = Rol::where('nombre', 'Análisis')->firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'Laura Méndez',
                'username' => 'lmendez',
                'email' => 'lmendez@example.com',
                'rol_id' => $rol->id,
                'permisos' => [
                    'historias-clinicas' => ['ver' => true, 'crear' => false, 'editar' => true, 'eliminar' => false],
                ],
            ])
            ->assertRedirect(route('users.index'));

        $usuario = User::where('username', 'lmendez')->firstOrFail();
        $this->assertSame($rol->id, $usuario->rol_id);
        $this->assertTrue($usuario->puede('historias-clinicas', 'editar'));
    }
}
