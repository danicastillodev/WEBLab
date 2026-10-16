<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    public function test_crea_un_producto_con_precio_por_grupo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('productos.store'), [
                'nombre' => 'PERFIL BASICO',
                'precio_unitario' => 50,
                'unidad_precio' => 'grupo',
                'activo' => true,
            ])
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'nombre' => 'PERFIL BASICO',
            'precio_unitario' => 50,
            'unidad_precio' => 'grupo',
        ]);
    }

    public function test_rechaza_una_unidad_de_precio_invalida(): void
    {
        $this->actingAs($this->admin())
            ->post(route('productos.store'), [
                'nombre' => 'PERFIL BASICO',
                'precio_unitario' => 50,
                'unidad_precio' => 'kilo',
            ])
            ->assertSessionHasErrors('unidad_precio');
    }

    public function test_por_defecto_el_precio_es_por_muestra(): void
    {
        $producto = Producto::create(['nombre' => 'COPROLOGICO', 'precio_unitario' => 120, 'activo' => true]);

        $this->assertSame('muestra', $producto->fresh()->unidad_precio);
    }

    public function test_filtra_el_listado_por_status(): void
    {
        Producto::create(['nombre' => 'ACTIVO', 'precio_unitario' => 10, 'activo' => true]);
        Producto::create(['nombre' => 'INACTIVO', 'precio_unitario' => 10, 'activo' => false]);

        $this->actingAs($this->admin())
            ->get(route('productos.index', ['activo' => '0']))
            ->assertInertia(fn ($page) => $page
                ->has('productos.data', 1)
                ->where('productos.data.0.nombre', 'INACTIVO'));

        $this->actingAs($this->admin())
            ->get(route('productos.index'))
            ->assertInertia(fn ($page) => $page->has('productos.data', 2));
    }
}
