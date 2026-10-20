<?php

namespace Tests\Feature;

use App\Models\ParametroOperacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParametrosOperacionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_la_pagina_carga_con_los_valores_por_defecto_sin_registro_previo(): void
    {
        $this->actingAs($this->admin())
            ->get(route('parametros-operacion.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('parametros.bd_ruta', '192.168.1.50')
                ->where('parametros.bd_nombre', 'NETLAB')
                ->where('parametros.proximo_folio', 55351));
    }

    public function test_guarda_los_parametros_por_primera_vez(): void
    {
        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'folio_automatico_activo' => true,
                'proximo_folio' => 100,
                'iva_porcentaje' => 16,
            ])
            ->assertRedirect(route('parametros-operacion.edit'));

        $this->assertDatabaseHas('parametros_operacion', [
            'proximo_folio' => 100,
            'iva_porcentaje' => 16,
        ]);
    }

    public function test_actualiza_el_registro_existente_en_vez_de_duplicarlo(): void
    {
        ParametroOperacion::create([
            'proximo_folio' => 1, 'iva_porcentaje' => 16,
        ]);

        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'folio_automatico_activo' => false,
                'proximo_folio' => 1, 'iva_porcentaje' => 16,
            ])
            ->assertRedirect(route('parametros-operacion.edit'));

        $this->assertSame(1, ParametroOperacion::count());
        $this->assertDatabaseHas('parametros_operacion', ['folio_automatico_activo' => false]);
    }

    public function test_muestra_el_proximo_numero_de_caso_del_ejercicio_en_curso(): void
    {
        $this->travelTo('2026-10-20');

        $this->actingAs($this->admin())
            ->get(route('parametros-operacion.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('numeroCaso.ejercicio', 2026)
                ->where('numeroCaso.proximo', 1));
    }

    public function test_guarda_el_proximo_numero_de_caso_ligado_al_ejercicio_en_curso(): void
    {
        $this->travelTo('2026-10-20');

        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'proximo_folio' => 1, 'iva_porcentaje' => 16,
                'proximo_numero_caso' => 350,
            ])
            ->assertRedirect(route('parametros-operacion.edit'));

        $this->assertDatabaseHas('parametros_operacion', [
            'proximo_numero_caso' => 350,
            'ejercicio_numero_caso' => 2026,
        ]);

        $this->get(route('parametros-operacion.edit'))
            ->assertInertia(fn ($page) => $page->where('numeroCaso.proximo', 350));
    }

    public function test_no_permite_disminuir_el_proximo_numero_de_caso_en_el_ejercicio(): void
    {
        $this->travelTo('2026-10-20');
        ParametroOperacion::create([
            'proximo_folio' => 1, 'iva_porcentaje' => 16,
            'proximo_numero_caso' => 350, 'ejercicio_numero_caso' => 2026,
        ]);

        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'proximo_folio' => 1, 'iva_porcentaje' => 16,
                'proximo_numero_caso' => 349,
            ])
            ->assertSessionHasErrors('proximo_numero_caso');

        $this->assertDatabaseHas('parametros_operacion', ['proximo_numero_caso' => 350]);
    }

    public function test_no_permite_vaciar_el_proximo_numero_de_caso(): void
    {
        $this->travelTo('2026-10-20');

        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'proximo_folio' => 1, 'iva_porcentaje' => 16,
                'proximo_numero_caso' => null,
            ])
            ->assertSessionHasErrors('proximo_numero_caso');
    }

    public function test_un_numero_configurado_en_otro_ejercicio_no_limita_el_actual(): void
    {
        $this->travelTo('2026-10-20');
        ParametroOperacion::create([
            'proximo_folio' => 1, 'iva_porcentaje' => 16,
            'proximo_numero_caso' => 900, 'ejercicio_numero_caso' => 2025,
        ]);

        $this->actingAs($this->admin())
            ->put(route('parametros-operacion.update'), [
                'proximo_folio' => 1, 'iva_porcentaje' => 16,
                'proximo_numero_caso' => 10,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('parametros_operacion', [
            'proximo_numero_caso' => 10, 'ejercicio_numero_caso' => 2026,
        ]);
    }
}
