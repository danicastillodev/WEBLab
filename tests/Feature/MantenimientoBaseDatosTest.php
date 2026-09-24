<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantenimientoBaseDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_carga_para_un_administrador(): void
    {
        $admin = User::factory()->create(['es_admin' => true]);

        $this->actingAs($admin)
            ->get(route('mantenimiento-base-datos.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Configuracion/MantenimientoBaseDatos/Index'));
    }
}
