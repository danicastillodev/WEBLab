<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Muestra;
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

    private int $rfcConsecutivo = 0;

    private function admin(): User
    {
        return User::factory()->create(['es_admin' => true]);
    }

    private function propietario(): Propietario
    {
        return Propietario::create(['nombre' => 'Juan', 'apellidos' => 'Pérez', 'telefono' => '3311223344']);
    }

    private function cliente(array $datos = []): Cliente
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);

        return Cliente::create($datos + [
            'nombre' => 'Agropecuaria del Bajío SA de CV',
            // Consecutivo para no chocar con el índice único de clientes.rfc.
            'rfc' => 'ABA010101'.str_pad((string) ++$this->rfcConsecutivo, 3, '0', STR_PAD_LEFT),
            'telefono' => '3312345678',
            'calle' => 'Av. Vallarta', 'numero_exterior' => '100', 'colonia' => 'Americana',
            'codigo_postal' => '44160', 'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
        ]);
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

    public function test_el_listado_requiere_algun_permiso_de_notas_de_pago(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('notas-pago.index'))
            ->assertForbidden();

        $user = User::factory()->create(['es_admin' => false]);
        $user->permisosGranulares()->create(['clave' => 'facturacion.modificar_facturas']);

        $this->actingAs($user)
            ->get(route('notas-pago.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('NotasPago/Index')->where('puede.crear', false));
    }

    public function test_el_listado_muestra_las_notas_y_filtra_por_folio_y_pagada(): void
    {
        $cliente = $this->cliente();
        $base = ['cliente_id' => $cliente->id, 'concepto' => 'Laboratorios', 'tipo_pago' => 'Contado',
            'subtotal' => 100, 'iva_porcentaje' => 16, 'iva_monto' => 16, 'total' => 116];
        $capturista = User::factory()->create(['name' => 'Ana Capturista']);
        NotaPago::create($base + ['folio' => 7777, 'pagada' => true, 'created_by' => $capturista->id]);
        NotaPago::create($base + ['folio' => 8888, 'pagada' => false, 'cancelada' => true, 'motivo_cancelacion' => 'Error de captura']);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('notas-pago.index'))
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Index')
                ->has('notas.data', 2)
                ->where('notas.data.0.folio', 8888)
                ->where('notas.data.0.cliente.nombre', $cliente->nombre)
                ->where('notas.data.0.cancelada', true)
                ->where('notas.data.1.cancelada', false)
                ->where('notas.data.1.creado_por.name', 'Ana Capturista'));

        $this->actingAs($admin)
            ->get(route('notas-pago.index', ['buscar' => '7777']))
            ->assertInertia(fn ($page) => $page->has('notas.data', 1)->where('notas.data.0.folio', 7777));

        $this->actingAs($admin)
            ->get(route('notas-pago.index', ['pagada' => '0']))
            ->assertInertia(fn ($page) => $page->has('notas.data', 1)->where('notas.data.0.folio', 8888));
    }

    public function test_imprime_la_nota_con_los_datos_del_cliente_los_conceptos_y_el_total_con_letra(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 55586]);
        $producto = Producto::create(['nombre' => 'BRUCELA FIJACION DE COMPLEMENTO', 'precio_unitario' => 155.17, 'activo' => true]);
        [$historia] = $this->historiaConMuestra('Brucela', 1, $producto->id);
        $cliente = $this->cliente(['nombre' => 'Jesús Eduardo Sandoval Jiménez', 'calle' => 'Francisco I Madero', 'numero_exterior' => '254']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('notas-pago.store'), [
            'cliente_id' => $cliente->id,
            'concepto' => 'Laboratorios',
            'tipo_pago' => 'Pago con tarjeta',
            'pagada' => true,
            'historia_clinica_ids' => [$historia->id],
        ]);
        $nota = NotaPago::where('folio', 55586)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('notas-pago.imprimir', $nota))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Imprimir')
                ->where('nota.folio', 55586)
                ->where('nota.fecha', now()->format('d/m/Y'))
                ->where('nota.cliente.nombre', 'Jesús Eduardo Sandoval Jiménez')
                ->where('nota.cliente.domicilio', 'Francisco I Madero # 254')
                ->where('nota.cliente.poblacion', 'Guadalajara, Jalisco')
                ->where('nota.cliente.rfc', $cliente->rfc)
                ->where('nota.conceptos.0.descripcion', 'BRUCELA FIJACION DE COMPLEMENTO')
                ->where('nota.conceptos.0.numero_caso', $historia->numero_caso)
                ->where('nota.subtotal', '155.17')
                ->where('nota.iva_monto', '24.83')
                ->where('nota.total', '180.00')
                ->where('nota.total_letra', 'CIENTO OCHENTA PESOS 00/100 M.N.')
                ->where('nota.pagada', true)
                ->where('nota.tipo_pago', 'Pago con tarjeta'));
    }

    public function test_imprimir_la_nota_requiere_algun_permiso_de_notas_de_pago(): void
    {
        $nota = NotaPago::create(['folio' => 1, 'cliente_id' => $this->cliente()->id, 'concepto' => 'Laboratorios',
            'tipo_pago' => 'Contado', 'subtotal' => 100, 'iva_porcentaje' => 16, 'iva_monto' => 16, 'total' => 116]);

        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('notas-pago.imprimir', $nota))
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

    public function test_la_nota_guarda_al_usuario_que_la_genero_y_editarla_no_lo_cambia(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $capturista = $this->usuarioCon('facturacion.agregar_facturas');

        $this->actingAs($capturista)
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertRedirect(route('notas-pago.index'));

        $nota = NotaPago::firstOrFail();
        $this->assertSame($capturista->id, $nota->created_by);
        $this->assertTrue($nota->creadoPor->is($capturista));

        // Quien la edita después no se vuelve su autor.
        $this->actingAs($this->admin())
            ->put(route('notas-pago.update', $nota), [
                'cliente_id' => $nota->cliente_id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame($capturista->id, $nota->fresh()->created_by);
    }

    public function test_crea_una_nota_de_pago_a_partir_de_una_historia_clinica_y_calcula_los_totales(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 100]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia, $propietario] = $this->historiaConMuestra('Antibiograma', 2, $producto->id);

        $response = $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'pagada' => true,
                'historia_clinica_ids' => [$historia->id],
            ]);

        $response->assertRedirect(route('notas-pago.index'));

        $this->assertDatabaseHas('notas_pago', [
            'folio' => 100,
            'propietario_id' => null,
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
            'historia_clinica_id' => $historia->id,
        ]);

        // El folio siguiente avanza para la próxima nota.
        $this->assertSame(101, ParametroOperacion::first()->proximo_folio);
    }

    public function test_al_guardar_entrega_el_folio_y_la_nota_para_preguntar_si_se_imprime(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 555]);
        [$historia] = $this->historiaConMuestra('Antibiograma');

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertRedirect(route('notas-pago.index'))
            ->assertSessionHas('nota_pago_folio', 555)
            ->assertSessionHas('nota_pago_id', NotaPago::where('folio', 555)->value('id'));

        $this->actingAs($this->admin())
            ->get(route('notas-pago.index'))
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Index')
                ->where('flash.nota_pago_folio', 555));
    }

    public function test_ignora_los_conceptos_enviados_por_el_cliente_y_los_reconstruye_desde_las_historias(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia, $propietario] = $this->historiaConMuestra('Antibiograma', 1, $producto->id);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
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

    public function test_respeta_la_cantidad_ajustada_pero_no_el_precio(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia] = $this->historiaConMuestra('Antibiograma', 1, $producto->id);
        $muestra = $historia->muestras()->first();

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
                'cantidades' => [$muestra->id => 3],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nota_pago_conceptos', ['cantidad' => 3, 'precio_unitario' => 100, 'importe' => 300]);
        $this->assertDatabaseHas('notas_pago', ['subtotal' => 300, 'total' => 348]);
        // La muestra de la historia clínica no cambia.
        $this->assertSame(1, (int) $muestra->fresh()->cantidad);
    }

    public function test_rechaza_cantidades_negativas_y_exige_facturar_al_menos_un_concepto(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma', 1);
        $muestra = $historia->muestras()->first();
        $datos = [
            'cliente_id' => $this->cliente()->id,
            'concepto' => 'Laboratorios',
            'tipo_pago' => 'Contado',
            'historia_clinica_ids' => [$historia->id],
        ];

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), $datos + ['cantidades' => [$muestra->id => -1]])
            ->assertSessionHasErrors("cantidades.{$muestra->id}");

        // 0 deja la muestra fuera de la nota; si no queda ningún concepto, no hay nota.
        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), $datos + ['cantidades' => [$muestra->id => 0]])
            ->assertSessionHasErrors('cantidades');

        $this->assertDatabaseCount('notas_pago', 0);
    }

    public function test_ignora_el_iva_enviado_por_el_cliente_y_usa_el_de_parametros_operacion(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 8, 'proximo_folio' => 1]);
        [$historia, $propietario] = $this->historiaConMuestra('Consulta', 1);
        $this->asignarPrecioManual($historia, 50);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
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
                'cliente_id' => $this->cliente()->id,
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
        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [],
            ])
            ->assertSessionHasErrors('historia_clinica_ids');
    }

    public function test_no_permite_facturar_una_historia_ya_facturada_por_completo(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $this->crearNota([$historia]);

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
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

    public function test_no_ofrece_una_historia_clinica_ya_facturada_por_completo(): void
    {
        [$historiaLigada] = $this->historiaConMuestra('ANTIBIOGRAMA');
        [$historiaLibre] = $this->historiaConMuestra('HEMOGRAMA');
        $this->crearNota([$historiaLigada]);

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

    /**
     * La historia clínica ya no captura producto: el precio sale del producto
     * asociado al servicio de la muestra (el primero activo dado de alta).
     */
    public function test_sin_producto_en_la_muestra_usa_el_producto_activo_del_servicio(): void
    {
        [$historia] = $this->historiaConMuestra('Coprológico', 2);
        $servicio = $historia->muestras()->first()->servicio;

        Producto::create(['nombre' => 'COPRO INACTIVO', 'servicio_id' => $servicio->id, 'precio_unitario' => 999, 'activo' => false]);
        $copro = Producto::create(['nombre' => 'COPROLOGICO', 'servicio_id' => $servicio->id, 'precio_unitario' => 120, 'activo' => true]);
        Producto::create(['nombre' => 'COPRO URGENTE', 'servicio_id' => $servicio->id, 'precio_unitario' => 200, 'activo' => true]);

        $concepto = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.conceptos-de-historias', ['historia_clinica_ids' => [$historia->id]]))
            ->assertOk()
            ->json('conceptos.0');

        $this->assertSame($copro->id, $concepto['producto_id']);
        $this->assertSame('COPROLOGICO', $concepto['descripcion']);
        $this->assertSame('120.00', $concepto['precio_unitario']);
    }

    public function test_guarda_la_relacion_con_varias_historias_clinicas_seleccionadas(): void
    {
        [$historiaUno, $propietario] = $this->historiaConMuestra('ANTIBIOGRAMA');
        [$historiaDos] = $this->historiaConMuestra('HEMOGRAMA');

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
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

        [$miaYaLigada] = $this->historiaConMuestra('Antibiograma');
        $miaYaLigada->update(['created_by' => $admin->id]);
        $this->crearNota([$miaYaLigada]);

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

    public function test_la_nota_de_pago_requiere_un_cliente(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertSessionHasErrors('cliente_id');

        $this->assertSame(0, NotaPago::count());
    }

    public function test_guarda_el_cliente_en_la_nota_de_pago(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $cliente = $this->cliente();

        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), [
                'cliente_id' => $cliente->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($cliente->id, NotaPago::firstOrFail()->cliente_id);
    }

    public function test_el_alta_de_cliente_desde_la_nota_solo_exige_el_nombre(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('notas-pago.store-cliente'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre'])
            ->assertJsonMissingValidationErrors([
                'rfc', 'telefono', 'calle', 'numero_exterior', 'colonia',
                'codigo_postal', 'estado_id', 'municipio_id',
            ]);

        $this->actingAs($this->admin())
            ->postJson(route('notas-pago.store-cliente'), ['nombre' => 'Sólo nombre', 'rfc' => ''])
            ->assertCreated()
            ->assertJsonPath('rfc', null);
    }

    public function test_crea_un_cliente_con_todos_sus_datos(): void
    {
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Zapopan', 'estado_id' => $estado->id]);

        $this->actingAs($this->admin())
            ->postJson(route('notas-pago.store-cliente'), [
                'nombre' => 'Granja La Esperanza',
                'rfc' => 'gle990101ab1',
                'telefono' => '3311223344',
                'calle' => 'Camino Real', 'numero_exterior' => '5', 'colonia' => 'Centro',
                'codigo_postal' => '45100', 'estado_id' => $estado->id, 'municipio_id' => $municipio->id,
            ])
            ->assertCreated()
            ->assertJsonPath('rfc', 'GLE990101AB1')
            ->assertJsonPath('municipio.nombre', 'Zapopan');
    }

    public function test_rechaza_un_municipio_que_no_pertenece_al_estado(): void
    {
        $jalisco = Estado::where('clave', 'JAL')->firstOrFail();
        $otroEstado = Estado::where('id', '!=', $jalisco->id)->firstOrFail();
        $municipioAjeno = Municipio::firstOrCreate(['nombre' => 'Municipio ajeno', 'estado_id' => $otroEstado->id]);

        $this->actingAs($this->admin())
            ->postJson(route('notas-pago.store-cliente'), [
                'nombre' => 'Granja', 'rfc' => 'GRA990101AB1', 'telefono' => '3311223344',
                'calle' => 'Calle', 'numero_exterior' => '1', 'colonia' => 'Centro',
                'codigo_postal' => '45100', 'estado_id' => $jalisco->id, 'municipio_id' => $municipioAjeno->id,
            ])
            ->assertJsonValidationErrors('municipio_id');
    }

    public function test_no_elimina_un_cliente_con_notas_de_pago(): void
    {
        $cliente = $this->cliente();
        NotaPago::create(['folio' => 1, 'cliente_id' => $cliente->id, 'concepto' => 'Laboratorios', 'tipo_pago' => 'Contado']);

        $this->actingAs($this->admin())
            ->deleteJson(route('notas-pago.destroy-cliente', $cliente))
            ->assertUnprocessable();

        $this->assertModelExists($cliente);
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

    /** @param  array<int, HistoriaClinica>  $historias */
    private function crearNota(array $historias, array $extra = []): NotaPago
    {
        $this->actingAs($this->admin())
            ->post(route('notas-pago.store'), $extra + [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => array_map(fn ($h) => $h->id, $historias),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('notas-pago.index'));

        return NotaPago::latest('id')->firstOrFail();
    }

    private function usuarioCon(string ...$claves): User
    {
        $user = User::factory()->create(['es_admin' => false]);
        foreach ($claves as $clave) {
            $user->permisosGranulares()->create(['clave' => $clave]);
        }

        return $user;
    }

    public function test_editar_precarga_la_nota_y_conserva_la_cantidad_ajustada(): void
    {
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia] = $this->historiaConMuestra('Antibiograma', 5, $producto->id);
        $muestra = $historia->muestras()->first();
        $nota = $this->crearNota([$historia], ['tipo_pago' => 'Cheque', 'pagada' => true, 'cantidades' => [$muestra->id => 4]]);

        $this->actingAs($this->admin())
            ->get(route('notas-pago.edit', $nota))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('NotasPago/Create')
                ->where('nota.folio', $nota->folio)
                ->where('nota.tipo_pago', 'Cheque')
                ->where('nota.historias.0.id', $historia->id)
                ->where('nota.conceptos.0.muestra_id', $muestra->id)
                ->where('nota.conceptos.0.cantidad', 4));
    }

    public function test_actualizar_conserva_el_folio_y_reconstruye_conceptos_e_historias(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 700]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historiaUno] = $this->historiaConMuestra('Antibiograma', 1, $producto->id);
        [$historiaDos] = $this->historiaConMuestra('Antibiograma', 3, $producto->id);
        $nota = $this->crearNota([$historiaUno]);
        $otroCliente = $this->cliente(['nombre' => 'OTRO CLIENTE', 'rfc' => 'XAXX010101000']);

        // El precio del catálogo cambió desde que se emitió la nota.
        $producto->update(['precio_unitario' => 50]);

        $this->actingAs($this->admin())
            ->put(route('notas-pago.update', $nota), [
                'cliente_id' => $otroCliente->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Depósito',
                'pagada' => true,
                // Se quita la historia uno y se agrega la dos.
                'historia_clinica_ids' => [$historiaDos->id],
            ])
            ->assertRedirect(route('notas-pago.index'))
            ->assertSessionHasNoErrors();

        $nota->refresh();
        $this->assertSame(700, $nota->folio);
        $this->assertSame($otroCliente->id, $nota->cliente_id);
        $this->assertSame('Depósito', $nota->tipo_pago);
        $this->assertTrue($nota->pagada);
        $this->assertSame([$historiaDos->id], $nota->historiasClinicas()->pluck('historias_clinicas.id')->all());
        $this->assertSame(1, $nota->conceptos()->count());
        $this->assertDatabaseHas('nota_pago_conceptos', [
            'nota_pago_id' => $nota->id, 'historia_clinica_id' => $historiaDos->id,
            'cantidad' => 3, 'precio_unitario' => 50, 'importe' => 150,
        ]);
        $this->assertEquals(174, $nota->total);
        // Editar no consume folio.
        $this->assertSame(701, ParametroOperacion::first()->proximo_folio);
    }

    public function test_editar_requiere_el_permiso_de_modificar_notas_de_pago(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);

        $this->actingAs($this->usuarioCon('facturacion.agregar_facturas'))
            ->get(route('notas-pago.edit', $nota))
            ->assertForbidden();

        $this->actingAs($this->usuarioCon('facturacion.modificar_facturas'))
            ->get(route('notas-pago.edit', $nota))
            ->assertOk();
    }

    public function test_al_editar_el_buscador_ofrece_las_historias_de_la_propia_nota(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);

        $sinNota = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.buscar-historias'))
            ->json('historias');
        $conNota = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.buscar-historias', ['nota_pago_id' => $nota->id]))
            ->json('historias');

        $this->assertNotContains($historia->id, array_column($sinNota, 'id'));
        $this->assertContains($historia->id, array_column($conNota, 'id'));
    }

    public function test_cancelar_exige_motivo_conserva_el_folio_y_libera_las_historias(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('notas-pago.cancelar', $nota), ['motivo_cancelacion' => ''])
            ->assertSessionHasErrors('motivo_cancelacion');
        $this->assertFalse($nota->fresh()->cancelada);

        $this->actingAs($admin)
            ->patch(route('notas-pago.cancelar', $nota), ['motivo_cancelacion' => 'El caso tenía 65 muestras en físico'])
            ->assertRedirect(route('notas-pago.index'));

        $nota->refresh();
        $this->assertTrue($nota->cancelada);
        $this->assertSame('El caso tenía 65 muestras en físico', $nota->motivo_cancelacion);
        $this->assertSame($admin->id, $nota->cancelada_por);
        $this->assertNotNull($nota->cancelada_at);
        // La nota cancelada sigue existiendo con sus conceptos.
        $this->assertSame(1, $nota->conceptos()->count());

        // La historia vuelve a ofrecerse y puede facturarse en otra nota.
        $ofrecidas = $this->actingAs($admin)->getJson(route('notas-pago.buscar-historias'))->json('historias');
        $this->assertContains($historia->id, array_column($ofrecidas, 'id'));
        $nueva = $this->crearNota([$historia]);
        $this->assertNotSame($nota->id, $nueva->id);
    }

    public function test_cancelar_requiere_el_permiso_de_cancelar_notas_de_pago(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);

        $this->actingAs($this->usuarioCon('facturacion.modificar_facturas'))
            ->patch(route('notas-pago.cancelar', $nota), ['motivo_cancelacion' => 'Error'])
            ->assertForbidden();

        $this->assertFalse($nota->fresh()->cancelada);
    }

    public function test_una_nota_cancelada_no_se_puede_editar(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);
        $nota->update(['cancelada' => true, 'motivo_cancelacion' => 'Error']);

        $this->actingAs($this->admin())
            ->get(route('notas-pago.edit', $nota))
            ->assertRedirect(route('notas-pago.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->admin())
            ->put(route('notas-pago.update', $nota), [
                'cliente_id' => $nota->cliente_id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Efectivo',
                'pagada' => true,
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertRedirect(route('notas-pago.index'));

        // Sigue como se guardó (no pagada, por lo tanto a crédito).
        $this->assertSame('Crédito', $nota->fresh()->tipo_pago);
        $this->assertFalse($nota->fresh()->pagada);
    }

    public function test_una_nota_no_pagada_siempre_queda_a_credito(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        [$historiaUno] = $this->historiaConMuestra('Antibiograma');
        [$historiaDos] = $this->historiaConMuestra('Antibiograma');

        // Aunque el navegador mande otro tipo de pago, sin pagar es Crédito.
        $noPagada = $this->crearNota([$historiaUno], ['tipo_pago' => 'Transferencia', 'pagada' => false]);
        $this->assertSame('Crédito', $noPagada->tipo_pago);

        // Pagada, se respeta el tipo de pago elegido.
        $pagada = $this->crearNota([$historiaDos], ['tipo_pago' => 'Transferencia', 'pagada' => true]);
        $this->assertSame('Transferencia', $pagada->tipo_pago);

        // Al editarla y desmarcar "Pagado", vuelve a crédito.
        $this->actingAs($this->admin())
            ->put(route('notas-pago.update', $pagada), [
                'cliente_id' => $pagada->cliente_id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Transferencia',
                'pagada' => false,
                'historia_clinica_ids' => [$historiaDos->id],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('Crédito', $pagada->fresh()->tipo_pago);
    }

    public function test_eliminar_borra_la_nota_y_libera_sus_historias_solo_para_administradores(): void
    {
        [$historia] = $this->historiaConMuestra('Antibiograma');
        $nota = $this->crearNota([$historia]);

        $this->actingAs($this->usuarioCon('facturacion.cancelar_factura', 'facturacion.modificar_facturas'))
            ->delete(route('notas-pago.destroy', $nota))
            ->assertForbidden();
        $this->assertDatabaseHas('notas_pago', ['id' => $nota->id]);

        $this->actingAs($this->admin())
            ->delete(route('notas-pago.destroy', $nota))
            ->assertRedirect(route('notas-pago.index'));

        $this->assertDatabaseMissing('notas_pago', ['id' => $nota->id]);
        $this->assertDatabaseMissing('nota_pago_conceptos', ['nota_pago_id' => $nota->id]);
        $this->assertDatabaseMissing('nota_pago_historia_clinica', ['nota_pago_id' => $nota->id]);
        $this->assertFalse($historia->notasPagoVigentes()->exists());
    }

    public function test_una_historia_se_reparte_en_varias_notas_hasta_completar_sus_muestras(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia] = $this->historiaConMuestra('Antibiograma', 10, $producto->id);
        $muestraA = $historia->muestras()->first();
        $muestraB = $historia->muestras()->create(['servicio_id' => $muestraA->servicio_id, 'producto_id' => $producto->id, 'cantidad' => 5]);
        $admin = $this->admin();

        // Primera nota: 6 de la muestra A y nada de la B.
        $primera = $this->crearNota([$historia], ['cantidades' => [$muestraA->id => 6, $muestraB->id => 0]]);
        $this->assertSame(1, $primera->conceptos()->count());
        $this->assertDatabaseHas('nota_pago_conceptos', ['nota_pago_id' => $primera->id, 'muestra_id' => $muestraA->id, 'cantidad' => 6]);

        // La historia sigue ofreciéndose, marcada como facturada parcialmente,
        // y sus conceptos proponen sólo lo que falta.
        $ofrecida = collect($this->actingAs($admin)->getJson(route('notas-pago.buscar-historias'))->json('historias'))
            ->firstWhere('id', $historia->id);
        $this->assertNotNull($ofrecida);
        $this->assertTrue($ofrecida['facturada_parcialmente']);

        $conceptos = collect($this->actingAs($admin)
            ->getJson(route('notas-pago.conceptos-de-historias', ['historia_clinica_ids' => [$historia->id]]))
            ->json('conceptos'))->keyBy('muestra_id');
        $this->assertSame(4, $conceptos[$muestraA->id]['por_facturar']);
        $this->assertSame(4, $conceptos[$muestraA->id]['cantidad']);
        $this->assertSame(10, $conceptos[$muestraA->id]['cantidad_muestra']);
        $this->assertSame(5, $conceptos[$muestraB->id]['por_facturar']);

        // Segunda nota con lo propuesto: completa la historia.
        $segunda = $this->crearNota([$historia]);
        $this->assertDatabaseHas('nota_pago_conceptos', ['nota_pago_id' => $segunda->id, 'muestra_id' => $muestraA->id, 'cantidad' => 4]);
        $this->assertDatabaseHas('nota_pago_conceptos', ['nota_pago_id' => $segunda->id, 'muestra_id' => $muestraB->id, 'cantidad' => 5]);
        $this->assertSame(2, $historia->notasPagoVigentes()->count());

        // Ya facturada por completo: ni se ofrece ni se puede volver a facturar.
        $ids = array_column($this->actingAs($admin)->getJson(route('notas-pago.buscar-historias'))->json('historias'), 'id');
        $this->assertNotContains($historia->id, $ids);
        $this->actingAs($admin)
            ->post(route('notas-pago.store'), [
                'cliente_id' => $this->cliente()->id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
            ])
            ->assertSessionHasErrors('historia_clinica_ids');

        // Al cancelar la primera, sus 6 unidades vuelven a quedar por facturar.
        $primera->update(['cancelada' => true, 'motivo_cancelacion' => 'Prueba']);
        $conceptos = collect($this->actingAs($admin)
            ->getJson(route('notas-pago.conceptos-de-historias', ['historia_clinica_ids' => [$historia->id]]))
            ->json('conceptos'))->keyBy('muestra_id');
        $this->assertSame(6, $conceptos[$muestraA->id]['por_facturar']);
        $this->assertFalse($conceptos->has($muestraB->id));
    }

    public function test_al_editar_lo_que_factura_la_propia_nota_no_cuenta_como_facturado(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        $producto = Producto::create(['nombre' => 'ANTIBIOGRAMA', 'precio_unitario' => 100, 'activo' => true]);
        [$historia] = $this->historiaConMuestra('Antibiograma', 10, $producto->id);
        $muestraA = $historia->muestras()->first();
        $muestraB = $historia->muestras()->create(['servicio_id' => $muestraA->servicio_id, 'producto_id' => $producto->id, 'cantidad' => 5]);

        $primera = $this->crearNota([$historia], ['cantidades' => [$muestraA->id => 6, $muestraB->id => 0]]);
        $this->crearNota([$historia], ['cantidades' => [$muestraA->id => 1, $muestraB->id => 0]]);

        // En la primera: A tiene 9 disponibles (10 menos el 1 de la otra nota)
        // y se precargan sus 6; B, que esta nota dejó fuera, aparece en 0.
        $this->actingAs($this->admin())
            ->get(route('notas-pago.edit', $primera))
            ->assertInertia(fn ($page) => $page
                ->where('nota.conceptos.0.muestra_id', $muestraA->id)
                ->where('nota.conceptos.0.por_facturar', 9)
                ->where('nota.conceptos.0.cantidad', 6)
                ->where('nota.conceptos.1.muestra_id', $muestraB->id)
                ->where('nota.conceptos.1.cantidad', 0));

        // Al guardar, la propia nota puede tomar lo que tenía y más.
        $this->actingAs($this->admin())
            ->put(route('notas-pago.update', $primera), [
                'cliente_id' => $primera->cliente_id,
                'concepto' => 'Laboratorios',
                'tipo_pago' => 'Contado',
                'historia_clinica_ids' => [$historia->id],
                'cantidades' => [$muestraA->id => 9, $muestraB->id => 5],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([9, 5], $primera->conceptos()->orderBy('muestra_id')->pluck('cantidad')->map(fn ($c) => (int) $c)->all());
    }

    /**
     * Historia con una muestra cuyo análisis tiene dos productos activos y
     * uno inactivo, más un producto de otro análisis.
     *
     * @return array{0: HistoriaClinica, 1: Muestra, 2: Producto, 3: Producto, 4: Producto, 5: Producto}
     */
    private function historiaConVariosProductos(): array
    {
        [$historia] = $this->historiaConMuestra('Brucela', 2);
        $muestra = $historia->muestras()->first();
        $tarjeta = Producto::create(['nombre' => 'BRUCELA TARJETA', 'servicio_id' => $muestra->servicio_id, 'precio_unitario' => 13.79, 'activo' => true]);
        $tubo = Producto::create(['nombre' => 'BRUCELA TARJETA (TUBO)', 'servicio_id' => $muestra->servicio_id, 'precio_unitario' => 15.52, 'activo' => true]);
        $inactivo = Producto::create(['nombre' => 'BRUCELA DESCONTINUADO', 'servicio_id' => $muestra->servicio_id, 'precio_unitario' => 1, 'activo' => false]);
        $otroServicio = Servicio::create(['clave' => 'OTRO1', 'descripcion' => 'Otro análisis']);
        $ajeno = Producto::create(['nombre' => 'HEMOGRAMA', 'servicio_id' => $otroServicio->id, 'precio_unitario' => 129.31, 'activo' => true]);

        return [$historia, $muestra, $tarjeta, $tubo, $inactivo, $ajeno];
    }

    public function test_cada_concepto_ofrece_solo_los_productos_activos_del_analisis_de_la_muestra(): void
    {
        [$historia, $muestra, $tarjeta, $tubo] = $this->historiaConVariosProductos();

        $concepto = $this->actingAs($this->admin())
            ->getJson(route('notas-pago.conceptos-de-historias', ['historia_clinica_ids' => [$historia->id]]))
            ->assertOk()
            ->json('conceptos.0');

        $this->assertSame($muestra->id, $concepto['muestra_id']);
        // Por omisión, el primero dado de alta.
        $this->assertSame($tarjeta->id, $concepto['producto_id']);
        $this->assertSame([$tarjeta->id, $tubo->id], array_column($concepto['productos'], 'id'));
    }

    public function test_se_puede_cambiar_el_producto_por_otro_del_mismo_analisis(): void
    {
        ParametroOperacion::create(['iva_porcentaje' => 16, 'proximo_folio' => 1]);
        [$historia, $muestra, , $tubo] = $this->historiaConVariosProductos();

        $nota = $this->crearNota([$historia], ['productos' => [$muestra->id => $tubo->id]]);

        // Nombre y precio salen del catálogo, no del navegador.
        $this->assertDatabaseHas('nota_pago_conceptos', [
            'nota_pago_id' => $nota->id,
            'muestra_id' => $muestra->id,
            'producto_id' => $tubo->id,
            'descripcion' => 'BRUCELA TARJETA (TUBO)',
            'cantidad' => 2,
            'precio_unitario' => 15.52,
            'importe' => 31.04,
        ]);

        // Al editar se precarga el producto elegido.
        $this->actingAs($this->admin())
            ->get(route('notas-pago.edit', $nota))
            ->assertInertia(fn ($page) => $page
                ->where('nota.conceptos.0.producto_id', $tubo->id)
                ->where('nota.conceptos.0.descripcion', 'BRUCELA TARJETA (TUBO)'));
    }

    public function test_rechaza_un_producto_que_no_corresponde_al_analisis_de_la_muestra(): void
    {
        [$historia, $muestra, , , $inactivo, $ajeno] = $this->historiaConVariosProductos();

        foreach ([$ajeno, $inactivo] as $producto) {
            $this->actingAs($this->admin())
                ->post(route('notas-pago.store'), [
                    'cliente_id' => $this->cliente()->id,
                    'concepto' => 'Laboratorios',
                    'tipo_pago' => 'Contado',
                    'historia_clinica_ids' => [$historia->id],
                    'productos' => [$muestra->id => $producto->id],
                ])
                ->assertSessionHasErrors("productos.{$muestra->id}");
        }

        $this->assertDatabaseCount('notas_pago', 0);
    }
}
