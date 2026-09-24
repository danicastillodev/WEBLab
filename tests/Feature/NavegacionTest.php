<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada enlace del sidebar debe llevar a una página que responda.
 *
 * Si una ruta con nombre no está registrada en routes/web.php, Ziggy lanza una
 * excepción al construir el enlace y React desmonta toda la aplicación: el
 * usuario ve una página en blanco, sin ningún error visible. Este test detecta
 * ese caso antes de que llegue al navegador.
 */
class NavegacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rutas que el sidebar enlaza y que deben responder 200.
     *
     * historias-clinicas.index queda fuera a propósito: redirige (302) para
     * añadir el filtro de fecha de hoy, y se comprueba por separado.
     *
     * @var list<string>
     */
    private const DESTINOS = [
        'dashboard',
        'historias-clinicas.create',
        'cuestionario.index',
        'cuestionario.create',
        'propietarios.index',
        'areas.index',
        'datos-generales.edit',
        'parametros-operacion.edit',
        'mantenimiento-base-datos.index',
        'municipios.index',
        'estados.index',
        'especies.index',
        'razas.index',
        'pruebas.index',
        'funcion-zootecnicas.index',
        'funcion-zootecnicas.create',
        'servicios.index',
        'servicios.create',
        'users.index',
        'users.create',
        'profile.edit',
    ];

    public function test_todas_las_paginas_del_sidebar_responden(): void
    {
        $admin = User::factory()->create(['es_admin' => true]);

        $estados = [];

        foreach (self::DESTINOS as $nombre) {
            $estados[$nombre] = $this->actingAs($admin)->get(route($nombre))->getStatusCode();
        }

        $this->assertSame(
            [],
            array_filter($estados, fn (int $codigo) => $codigo !== 200),
            'Estas rutas del sidebar no respondieron 200: '.json_encode($estados),
        );
    }

    public function test_el_listado_de_historias_redirige_al_filtro_de_hoy(): void
    {
        $admin = User::factory()->create(['es_admin' => true]);
        $hoy = now()->toDateString();

        $this->actingAs($admin)
            ->get(route('historias-clinicas.index'))
            ->assertRedirect(route('historias-clinicas.index', [
                'fecha_desde' => $hoy,
                'fecha_hasta' => $hoy,
                'ejercicio' => now()->year,
            ]));

        $this->actingAs($admin)
            ->get(route('historias-clinicas.index', ['fecha_desde' => $hoy, 'fecha_hasta' => $hoy]))
            ->assertOk();
    }
}
