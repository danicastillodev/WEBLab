<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $extra = []): array
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);

        return $extra + [
            'nombre' => 'Agropecuaria del Bajío SA de CV',
            'rfc' => 'ABA010101AB1',
            'telefono' => '3312345678',
            'calle' => 'Av. Vallarta', 'numero_exterior' => '100', 'numero_interior' => '',
            'colonia' => 'Americana', 'codigo_postal' => '44160',
            'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
        ];
    }

    public function test_el_listado_muestra_los_clientes_dados_de_alta_desde_la_nota_de_pago(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('notas-pago.store-cliente'), $this->datos())
            ->assertCreated();

        $this->actingAs($admin)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalogos/Clientes/Index')
                ->has('clientes.data', 1)
                ->where('clientes.data.0.rfc', 'ABA010101AB1')
                ->where('clientes.data.0.municipio.nombre', 'Guadalajara'));
    }

    public function test_busca_por_nombre_o_rfc(): void
    {
        Cliente::create($this->datos());
        Cliente::create($this->datos(['nombre' => 'Granja La Esperanza', 'rfc' => 'GLE990101AB1']));

        $this->actingAs($this->admin())
            ->get(route('clientes.index', ['buscar' => 'GLE99']))
            ->assertInertia(fn ($page) => $page
                ->has('clientes.data', 1)
                ->where('clientes.data.0.nombre', 'Granja La Esperanza'));
    }

    public function test_crea_un_cliente(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), $this->datos(['rfc' => 'aba010101ab1']))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', ['rfc' => 'ABA010101AB1', 'telefono' => '3312345678']);
    }

    public function test_exige_todos_los_datos_del_cliente(): void
    {
        $this->actingAs($this->admin())
            ->post(route('clientes.store'), ['nombre' => 'Sin datos'])
            ->assertSessionHasErrors(['rfc', 'telefono', 'calle', 'numero_exterior', 'colonia', 'codigo_postal', 'estado_id', 'municipio_id']);

        $this->assertSame(0, Cliente::count());
    }

    public function test_actualiza_un_cliente_conservando_su_propio_rfc(): void
    {
        $cliente = Cliente::create($this->datos());

        $this->actingAs($this->admin())
            ->patch(route('clientes.update', $cliente), $this->datos(['telefono' => '3399887766']))
            ->assertRedirect(route('clientes.index'));

        $this->assertSame('3399887766', $cliente->fresh()->telefono);
    }

    public function test_no_permite_repetir_el_rfc_de_otro_cliente(): void
    {
        Cliente::create($this->datos());
        $otro = Cliente::create($this->datos(['rfc' => 'GLE990101AB1']));

        $this->actingAs($this->admin())
            ->patch(route('clientes.update', $otro), $this->datos())
            ->assertSessionHasErrors('rfc');
    }

    public function test_elimina_un_cliente_sin_notas_de_pago(): void
    {
        $cliente = Cliente::create($this->datos());

        $this->actingAs($this->admin())
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertModelMissing($cliente);
    }

    public function test_no_elimina_un_cliente_con_notas_de_pago(): void
    {
        $cliente = Cliente::create($this->datos());
        NotaPago::create(['folio' => 1, 'cliente_id' => $cliente->id, 'concepto' => 'Laboratorios', 'tipo_pago' => 'Contado']);

        $this->actingAs($this->admin())
            ->delete(route('clientes.destroy', $cliente))
            ->assertSessionHas('error');

        $this->assertModelExists($cliente);
    }

    public function test_un_usuario_sin_permiso_no_puede_ver_el_listado(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('clientes.index'))
            ->assertForbidden();
    }
}
