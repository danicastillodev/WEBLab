<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ControlAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $atributos = [], array $permisos = []): User
    {
        $user = User::factory()->create($atributos + ['es_admin' => false]);

        foreach ($permisos as $modulo => $acciones) {
            $user->permissions()->create([
                'modulo' => $modulo,
                'ver' => $acciones['ver'] ?? false,
                'crear' => $acciones['crear'] ?? false,
                'editar' => $acciones['editar'] ?? false,
                'eliminar' => $acciones['eliminar'] ?? false,
            ]);
        }

        return $user->fresh();
    }

    // ---------------------------------------------------------------- permisos

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)->get(route('estados.index'))->assertForbidden();
    }

    public function test_usuario_con_permiso_de_ver_accede_al_listado(): void
    {
        $user = $this->usuario(permisos: ['estados' => ['ver' => true]]);

        $this->actingAs($user)->get(route('estados.index'))->assertOk();
    }

    public function test_permiso_de_ver_no_habilita_crear(): void
    {
        $user = $this->usuario(permisos: ['estados' => ['ver' => true]]);

        $this->actingAs($user)->get(route('estados.create'))->assertForbidden();
    }

    public function test_permiso_de_crear_habilita_el_formulario(): void
    {
        $user = $this->usuario(permisos: ['estados' => ['ver' => true, 'crear' => true]]);

        $this->actingAs($user)->get(route('estados.create'))->assertOk();
    }

    public function test_los_permisos_no_se_filtran_entre_modulos(): void
    {
        $user = $this->usuario(permisos: ['estados' => ['ver' => true]]);

        $this->actingAs($user)->get(route('municipios.index'))->assertForbidden();
    }

    public function test_el_administrador_omite_el_control_de_permisos(): void
    {
        $admin = User::factory()->create(['es_admin' => true]);

        $this->actingAs($admin)->get(route('estados.index'))->assertOk();
        $this->actingAs($admin)->get(route('municipios.index'))->assertOk();
    }

    public function test_las_rutas_sin_modulo_no_requieren_permiso(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    // ---------------------------------------------------------------- horario

    public function test_acceso_permitido_dentro_de_la_ventana(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-31 10:00:00')); // viernes

        $user = $this->usuario(['hora_inicio' => '08:00', 'hora_fin' => '18:00']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    public function test_acceso_denegado_fuera_de_la_ventana(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-31 19:30:00'));

        $user = $this->usuario(['hora_inicio' => '08:00', 'hora_fin' => '18:00']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_la_sesion_se_cierra_al_terminar_el_horario(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-31 17:59:00'));

        $user = $this->usuario(['hora_inicio' => '08:00', 'hora_fin' => '18:00']);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        // El usuario sigue navegando, pero su ventana ya se cerró.
        Carbon::setTestNow(Carbon::parse('2026-07-31 18:01:00'));

        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_ventana_que_cruza_la_medianoche(): void
    {
        $user = $this->usuario(['hora_inicio' => '22:00', 'hora_fin' => '06:00']);

        Carbon::setTestNow(Carbon::parse('2026-07-31 23:30:00'));
        $this->assertTrue($user->dentroDeHorario());

        Carbon::setTestNow(Carbon::parse('2026-07-31 03:00:00'));
        $this->assertTrue($user->dentroDeHorario());

        Carbon::setTestNow(Carbon::parse('2026-07-31 12:00:00'));
        $this->assertFalse($user->dentroDeHorario());
    }

    public function test_dias_permitidos_bloquean_el_fin_de_semana(): void
    {
        // Lunes a viernes.
        $user = $this->usuario(['dias_permitidos' => [1, 2, 3, 4, 5]]);

        Carbon::setTestNow(Carbon::parse('2026-07-31 10:00:00')); // viernes
        $this->assertTrue($user->dentroDeHorario());

        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00')); // sábado
        $this->assertFalse($user->dentroDeHorario());
    }

    public function test_sin_horario_configurado_no_hay_restriccion(): void
    {
        $user = $this->usuario();

        Carbon::setTestNow(Carbon::parse('2026-08-01 03:00:00'));

        $this->assertTrue($user->dentroDeHorario());
    }

    public function test_el_administrador_omite_el_horario(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 03:00:00'));

        $admin = User::factory()->create([
            'es_admin' => true,
            'hora_inicio' => '08:00',
            'hora_fin' => '18:00',
            'dias_permitidos' => [1],
        ]);

        $this->assertTrue($admin->dentroDeHorario());
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
