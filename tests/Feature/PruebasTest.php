<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\Producto;
use App\Models\Prueba;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruebasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_el_formulario_de_alta_recibe_el_catalogo_de_productos_activos(): void
    {
        Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 172.41, 'activo' => true]);
        Producto::create(['nombre' => 'DESCONTINUADO', 'precio_unitario' => 10, 'activo' => false]);

        $this->actingAs($this->admin())
            ->get(route('pruebas.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalogos/Pruebas/Create')
                ->has('productos', 1)
                ->where('productos.0.nombre', 'ANTIBIOGRAMA'));
    }

    public function test_crea_una_prueba_vinculada_a_un_producto(): void
    {
        $especie = Especie::create(['nombre' => 'Bovino']);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 172.41, 'activo' => true]);

        $this->actingAs($this->admin())
            ->post(route('pruebas.store'), [
                'nombre' => 'Antibiograma',
                'especie_id' => $especie->id,
                'producto_id' => $producto->id,
            ])
            ->assertRedirect(route('pruebas.index'));

        $this->assertDatabaseHas('pruebas', [
            'nombre' => 'Antibiograma',
            'producto_id' => $producto->id,
        ]);
    }

    public function test_una_prueba_puede_crearse_sin_producto_asociado(): void
    {
        $this->actingAs($this->admin())
            ->post(route('pruebas.store'), ['nombre' => 'Tuberculosis PPD'])
            ->assertRedirect(route('pruebas.index'));

        $this->assertDatabaseHas('pruebas', ['nombre' => 'Tuberculosis PPD', 'producto_id' => null]);
    }

    public function test_actualiza_el_producto_asociado_a_una_prueba(): void
    {
        $prueba = Prueba::create(['nombre' => 'Antibiograma']);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 172.41, 'activo' => true]);

        $this->actingAs($this->admin())
            ->patch(route('pruebas.update', $prueba), [
                'nombre' => $prueba->nombre,
                'producto_id' => $producto->id,
            ])
            ->assertRedirect(route('pruebas.index'));

        $this->assertDatabaseHas('pruebas', ['id' => $prueba->id, 'producto_id' => $producto->id]);
    }

    public function test_el_listado_muestra_el_producto_asociado(): void
    {
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 172.41, 'activo' => true]);
        Prueba::create(['nombre' => 'Antibiograma', 'producto_id' => $producto->id]);

        $this->actingAs($this->admin())
            ->get(route('pruebas.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pruebas.data.0.producto.nombre', 'ANTIBIOGRAMA'));
    }
}
