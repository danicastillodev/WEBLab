<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\CrearContrasenaController;
use App\Http\Middleware\VerificarHorario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Primer inicio de sesión de un usuario sin contraseña: se le pide crearla y
 * queda asignada a partir de ese momento.
 */
class CrearContrasenaTest extends TestCase
{
    use RefreshDatabase;

    private function sinContrasena(): User
    {
        return User::factory()->create(['username' => 'lmendez', 'name' => 'Laura Méndez', 'password' => null]);
    }

    public function test_el_usuario_sin_contrasena_es_enviado_a_crearla(): void
    {
        $user = $this->sinContrasena();

        $this->post('/login', ['username' => 'lmendez', 'password' => ''])
            ->assertRedirect(route('password.crear'))
            ->assertSessionHas(CrearContrasenaController::SESION, $user->id);

        $this->assertGuest();

        $this->get(route('password.crear'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/CrearContrasena')
                ->where('username', 'lmendez')
                ->where('nombre', 'Laura Méndez'));
    }

    public function test_crear_la_contrasena_la_asigna_e_inicia_sesion(): void
    {
        $user = $this->sinContrasena();

        $this->post('/login', ['username' => 'lmendez']);

        $this->post(route('password.crear'), [
            'password' => 'mi-contrasena-1',
            'password_confirmation' => 'mi-contrasena-1',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('mi-contrasena-1', $user->refresh()->password));

        // A partir de ahora entra con ella como cualquier usuario.
        $this->post('/logout');
        $this->post('/login', ['username' => 'lmendez', 'password' => 'mi-contrasena-1'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_la_contrasena_nueva_debe_confirmarse(): void
    {
        $user = $this->sinContrasena();

        $this->post('/login', ['username' => 'lmendez']);

        $this->post(route('password.crear'), [
            'password' => 'mi-contrasena-1',
            'password_confirmation' => 'otra-cosa-1',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertNull($user->refresh()->password);
    }

    public function test_sin_pasar_por_el_login_no_se_puede_crear(): void
    {
        $user = $this->sinContrasena();

        $this->get(route('password.crear'))->assertRedirect(route('login'));

        $this->post(route('password.crear'), [
            'password' => 'mi-contrasena-1',
            'password_confirmation' => 'mi-contrasena-1',
        ])->assertRedirect(route('login'));

        $this->assertNull($user->refresh()->password);
    }

    public function test_fuera_de_horario_se_avisa_antes_de_crear_la_contrasena(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-31 13:32:00'));

        // Ventana que cruza la medianoche: de 16:26 a 13:24 del día siguiente.
        $user = User::factory()->create([
            'username' => 'jperez',
            'password' => null,
            'hora_inicio' => '16:26',
            'hora_fin' => '13:24',
        ]);

        $this->post('/login', ['username' => 'jperez'])
            ->assertSessionHasErrors(['username' => VerificarHorario::MENSAJE])
            ->assertSessionMissing(CrearContrasenaController::SESION);

        $this->assertGuest();
        $this->assertNull($user->refresh()->password);
    }

    public function test_un_usuario_con_contrasena_no_puede_reemplazarla_asi(): void
    {
        User::factory()->create(['username' => 'jperez', 'password' => Hash::make('la-de-siempre-1')]);

        // Con contraseña, dejarla en blanco es un error y no lleva a crearla.
        $this->post('/login', ['username' => 'jperez', 'password' => ''])
            ->assertSessionHasErrors('password')
            ->assertSessionMissing(CrearContrasenaController::SESION);

        $this->get(route('password.crear'))->assertRedirect(route('login'));
    }
}
