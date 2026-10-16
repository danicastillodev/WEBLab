<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquier_usuario_ve_la_pagina_sin_permisos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('datos-personales.edit'))
            ->assertOk();
    }

    public function test_actualiza_nombre_y_correo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('datos-personales.update'), [
                'name' => 'Nuevo Nombre',
                'email' => 'nuevo@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('datos-personales.edit'));

        $user->refresh();
        $this->assertSame('Nuevo Nombre', $user->name);
        $this->assertSame('nuevo@example.com', $user->email);
    }

    public function test_rechaza_correo_de_otro_usuario(): void
    {
        $otro = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('datos-personales.update'), [
                'name' => $user->name,
                'email' => $otro->email,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_cambia_la_contrasena(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('datos-personales.update-password'), [
                'current_password' => 'password',
                'password' => 'nueva-contrasena',
                'password_confirmation' => 'nueva-contrasena',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('datos-personales.edit'));

        $this->assertTrue(Hash::check('nueva-contrasena', $user->refresh()->password));
    }

    public function test_exige_la_contrasena_actual_correcta(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('datos-personales.update-password'), [
                'current_password' => 'incorrecta',
                'password' => 'nueva-contrasena',
                'password_confirmation' => 'nueva-contrasena',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
