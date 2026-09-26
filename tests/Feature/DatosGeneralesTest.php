<?php

namespace Tests\Feature;

use App\Models\DatosGeneral;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatosGeneralesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_la_pagina_carga_sin_datos_previos(): void
    {
        $this->actingAs($this->admin())
            ->get(route('datos-generales.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('datosGenerales', null));
    }

    public function test_guarda_los_datos_generales_por_primera_vez(): void
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);

        $this->actingAs($this->admin())
            ->put(route('datos-generales.update'), [
                'razon_social' => 'Laboratorio S.A. de C.V.',
                'nombre_laboratorio' => 'WebLab',
                'direccion' => 'Av. Central 123',
                'colonia' => 'Centro',
                'estado_id' => $estado->id,
                'municipio_id' => $municipio->id,
                'jefe_laboratorio' => 'Dra. Ana Ruiz',
            ])
            ->assertRedirect(route('datos-generales.edit'));

        $this->assertDatabaseHas('datos_generales', [
            'razon_social' => 'Laboratorio S.A. de C.V.',
            'nombre_laboratorio' => 'WebLab',
        ]);
    }

    public function test_actualiza_el_registro_existente_en_vez_de_duplicarlo(): void
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        DatosGeneral::create([
            'razon_social' => 'Original', 'nombre_laboratorio' => 'WebLab',
            'direccion' => 'Av. Central 123', 'colonia' => 'Centro',
            'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
            'jefe_laboratorio' => 'Dra. Ana Ruiz',
        ]);

        $this->actingAs($this->admin())
            ->put(route('datos-generales.update'), [
                'razon_social' => 'Actualizada',
                'nombre_laboratorio' => 'WebLab',
                'direccion' => 'Av. Central 123',
                'colonia' => 'Centro',
                'estado_id' => $estado->id,
                'municipio_id' => $municipio->id,
                'jefe_laboratorio' => 'Dra. Ana Ruiz',
            ])
            ->assertRedirect(route('datos-generales.edit'));

        $this->assertSame(1, DatosGeneral::count());
        $this->assertDatabaseHas('datos_generales', ['razon_social' => 'Actualizada']);
    }
}
