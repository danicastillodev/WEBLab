<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\NotaPago;
use App\Models\ParametroOperacion;
use App\Models\Producto;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotasPagoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    private function propietario(): Propietario
    {
        return Propietario::create(['nombre' => 'Juan', 'apellidos' => 'Pérez', 'telefono' => '3311223344']);
    }

    /**
     * @return array{0: HistoriaClinica, 1: Propietario, 2: Direccion}
     */
    private function historiaConMuestra(string $nombreServicio, float $cantidad = 1, ?int $productoId = null): array
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        $propietario = $this->propietario();
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id,
            'calle' => 'Reforma', 'numero_exterior' => '2', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id, 'codigo_postal' => '44780',
        ]);
        $servicio = Servicio::create(['clave' => strtoupper(substr(md5($nombreServicio.microtime()), 0, 8)), 'descripcion' => $nombreServicio]);
        $especie = Especie::firstOrCreate(['nombre' => 'Bovino']);
        $raza = Raza::firstOrCreate(['nombre' => 'Holstein', 'especie_id' => $especie->id]);

        $historia = HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => now(),
            'edad_unidad' => 'NA',
        ]);
        $historia->muestras()->create(['servicio_id' => $servicio->id, 'producto_id' => $productoId, 'cantidad' => $cantidad]);

        return [$historia, $propietario, $direccion];
    }

    public function test_un_usuario_sin_el_permiso_granular_no_puede_entrar(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('notas-pago.create'))
            ->assertForbidden();
    }

    public function test_un_admin_puede_ver_el_formulario(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 55351]);

        $this->actingAs($this->admin())
            ->get(route('notas-pago.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Create')
                ->where('iva_porcentaje', 16));
    }

    public function test_crea_una_nota_de_pago_a_partir_de_una_historia_clinica_y_calcula_los_totales(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 100]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia, $propietario] = $this->historiaConMuestra('Antibiograma', 2, $producto->id);

        $response = $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'pagada' => true,
                'historia_clinica_ids' => [$historia->id],
            ]);

        $response->assertRedirect(route('notas-pago.create'));

        $this->assertDatabaseHas('notas_pago', [
            'folio' => 100,
            'propietario_id' => $propietario->id,
            'subtotal' => 200,
            'iva_porcentaje' => 16,
            'iva_monto' => 32,
            'total' => 232,
            'pagada' => true,
        ]);
        $this->assertDatabaseHas('nota_pago_conceptos', [
            'producto_id' => $producto->id,
            'descripcion' => 'ANTIBIOGRAMA',
            'cantidad' => 2,
            'precio_unitario' => 100,
            'importe' => 200,
        ]);

        // El folio siguiente avanza para la próxima nota.
        $this->assertSame(101, ParametroOperacion::first()->proximo_folio);
    }

    public function test_ignora_los_conceptos_enviados_por_el_cliente_y_los_reconstruye_desde_las_historias(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia, $propietario] = $this->historiaConMuestra('Antibiograma', 1, $producto->id);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
                // Un cliente malicioso podría intentar inyectar su propia cantidad/precio.
                'conceptos' => [
                    ['producto_id' => $producto->id, 'descripcion' => 'ANTIBIOGRAMA', 'cantidad' => 999, 'precio_unitario' => 0.01],
                ],
            ]);

        // El importe sale de la muestra real (1 x $100), no del arreglo manipulado.
        $this->assertDatabaseHas('nota_pago_conceptos', [
            'cantidad' => 1,
            'precio_unitario' => 100,
            'importe' => 100,
        ]);
        $this->assertDatabaseMissing('nota_pago_conceptos', ['cantidad' => 999]);
    }

    public function test_ignora_el_iva_enviado_por_el_cliente_y_usa_el_de_parametros_operacion(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 8, 'proximo_folio' => 1]);
        [$historia, $propietario] = $this->historiaConMuestra('Consulta', 1);
        $this->asignarPrecioManual($historia, 50);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
                // Un cliente malicioso podría intentar forzar su propio IVA.
                'iva_porcentaje' => 99,
            ]);

        $this->assertDatabaseHas('notas_pago', [
            'iva_porcentaje' => 8,
            'iva_monto' => 4,
            'total' => 54,
        ]);
    }

    public function test_usa_16_por_ciento_de_iva_cuando_no_existe_el_registro_de_parametros(): void
    {
        // Sin fila en parametros_operacion (instalación recién migrada): debe
        // usar el mismo valor por defecto que ParametrosOperacionController,
        // no 0%.
        [$historia, $propietario] = $this->historiaConMuestra('Consulta', 1);
        $this->asignarPrecioManual($historia, 100);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ]);

        $this->assertDatabaseHas('notas_pago', [
            'iva_porcentaje' => 16,
            'iva_monto' => 16,
            'total' => 116,
        ]);
    }

    public function test_requiere_al_menos_una_historia_clinica(): void
    {
        $propietario = $this->propietario();

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [],
            ])
            ->assertSessionHasErrors('historia_clinica_ids');
    }

    public function test_no_permite_ligar_una_historia_clinica_ya_ligada_a_otra_nota_de_pago(): void
    {
        [$historia, $propietario] = $this->historiaConMuestra('Antibiograma');
        $notaExistente = NotaPago::create([
            'folio' => 1, 'propietario_id' => $propietario->id,
            'concepto' => 'Laboratorios', 'tipo_pago' => 'Contado',
        ]);
        $notaExistente->historiasClinicas()->attach($historia->id);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertSessionHasErrors('historia_clinica_ids');

        $this->assertSame(1, NotaPago::count());
    }

    public function test_busca_historias_clinicas_por_numero_de_caso_o_nombre_del_propietario(): void
    {
        [$historia, $propietario] = $this->historiaConMuestra('ANTIBIOGRAMA');

        $this->actingAs($this->admin())
            ->getJson(route('notas-pago.buscar-historias', ['buscar' => (string) $historia->numero_caso]))
            ->assertOk()
            ->assertJsonFragment(['id' => $historia->id, 'numero_caso' => $historia->numero_caso]);

        $this->actingAs($this->admin())
            ->getJson(route('notas-pago.buscar-historias', ['buscar' => $propietario->apellidos]))
            ->assertOk()
            ->assertJsonFragment(['id' => $historia->id]);
    }

    public function test_no_ofrece_una_historia_clinica_ya_ligada_a_otra_nota_de_pago(): void
    {
        [$historiaLigada, $propietario] = $this->historiaConMuestra('ANTIBIOGRAMA');
        [$historiaLibre] = $this->historiaConMuestra('HEMOGRAMA');

        $notaExistente = NotaPago::create([
            'folio' => 1,
            'propietario_id' => $propietario->id,
            'concepto' => 'Laboratorios',
            'tipo_pago' => 'Contado',
        ]);
        $notaExistente->historiasClinicas()->attach($historiaLigada->id);

        $respuesta = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.buscar-historias'))
            ->assertOk()
            ->json('historias');

        $ids = array_column($respuesta, 'id');
        $this->assertNotContains($historiaLigada->id, $ids);
        $this->assertContains($historiaLibre->id, $ids);
    }

    public function test_combina_los_conceptos_de_varias_historias_clinicas_usando_el_producto_capturado_en_la_muestra(): void
    {
        $antibiograma = Producto::create(['nombre' => 'BTA25 - ANTIBIOGRAMA', 'precio_unitario' => 172.41, 'activo' => true]);
        $hemograma = Producto::create(['nombre' => 'HEMATOLOGICO', 'precio_unitario' => 50, 'activo' => true]);

        [$historiaUno] = $this->historiaConMuestra('Antibiograma', 3, $antibiograma->id);
        [$historiaDos] = $this->historiaConMuestra('Hemograma', 1, $hemograma->id);
        [$historiaTres] = $this->historiaConMuestra('Servicio sin producto capturado', 2);

        $respuesta = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.conceptos-de-historias', [
                'historia_clinica_ids' => [$historiaUno->id, $historiaDos->id, $historiaTres->id],
            ]))
            ->assertOk()
            ->json();

        $this->assertCount(3, $respuesta['historias']);
        $this->assertCount(3, $respuesta['conceptos']);
        $this->assertSame(
            [$antibiograma->id, $hemograma->id, null],
            array_column($respuesta['conceptos'], 'producto_id'),
        );
        $this->assertSame(['172.41', '50.00', 0], array_column($respuesta['conceptos'], 'precio_unitario'));
        $this->assertSame(
            [$historiaUno->numero_caso, $historiaDos->numero_caso, $historiaTres->numero_caso],
            array_column($respuesta['conceptos'], 'numero_caso'),
        );
    }

    public function test_guarda_la_relacion_con_varias_historias_clinicas_seleccionadas(): void
    {
        [$historiaUno, $propietario] = $this->historiaConMuestra('ANTIBIOGRAMA');
        [$historiaDos] = $this->historiaConMuestra('HEMOGRAMA');

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'propietario_id' => $propietario->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historiaUno->id, $historiaDos->id],
            ]);

        $notaPago = NotaPago::firstOrFail();
        $this->assertSame(
            [$historiaUno->id, $historiaDos->id],
            $notaPago->historiasClinicas()->pluck('historias_clinicas.id')->sort()->values()->all(),
        );
        $this->assertSame(2, $notaPago->conceptos()->count());
    }

    public function test_preseleccionar_hoy_sólo_incluye_las_historias_de_hoy_del_usuario_actual_sin_nota_de_pago(): void
    {
        $admin = $this->admin();
        $otroUsuario = $this->admin();

        [$miaDeHoy] = $this->historiaConMuestra('Antibiograma');
        $miaDeHoy->update(['created_by' => $admin->id]);

        [$deOtroUsuario] = $this->historiaConMuestra('Antibiograma');
        $deOtroUsuario->update(['created_by' => $otroUsuario->id]);

        [$miaDeAyer] = $this->historiaConMuestra('Antibiograma');
        $miaDeAyer->created_by = $admin->id;
        $miaDeAyer->created_at = now()->subDay();
        $miaDeAyer->save();

        [$miaYaLigada, $propietario] = $this->historiaConMuestra('Antibiograma');
        $miaYaLigada->update(['created_by' => $admin->id]);
        $notaExistente = NotaPago::create([
            'folio' => 1, 'propietario_id' => $propietario->id,
            'concepto' => 'Laboratorios', 'tipo_pago' => 'Contado',
        ]);
        $notaExistente->historiasClinicas()->attach($miaYaLigada->id);

        $this->actingAs($admin)
            ->get(route('notas-pago.create', ['preseleccionar_hoy' => 1]))
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Create')
                ->has('historias_preseleccionadas.historias', 1)
                ->where('historias_preseleccionadas.historias.0.id', $miaDeHoy->id));
    }

    public function test_sin_preseleccionar_hoy_no_manda_historias_preseleccionadas(): void
    {
        $this->actingAs($this->admin())
            ->get(route('notas-pago.create'))
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Create')
                ->where('historias_preseleccionadas', null));
    }

    /**
     * Vincula un producto con el precio dado a la primera muestra de la
     * historia, para pruebas que sólo necesitan verificar que el precio
     * llegue completo, sin que importe cuál producto es.
     */
    private function asignarPrecioManual(HistoriaClinica $historia, float $precio): void
    {
        $muestra = $historia->muestras()->first();
        $producto = Producto::create(['nombre' => 'PRODUCTO DE PRUEBA', 'precio_unitario' => $precio, 'activo' => true]);
        $muestra->update(['producto_id' => $producto->id]);
    }
}
