<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Diagnostico;
use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Muestra;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Captura de un diagnóstico desde el listado: guardar (en proceso), emitir y
 * cancelar, con sus validaciones y permisos.
 */
class DiagnosticoCapturaTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->area = Area::create(['nombre' => 'Serología']);
    }

    private function analisis(?Area $area = null, int $cantidad = 5): Muestra
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $estado = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estado->id]);
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id, 'calle' => 'Av. Central',
            'numero_exterior' => '10', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estado->id,
            'codigo_postal' => '44100',
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Caprino']);
        $raza = Raza::firstOrCreate(['nombre' => 'Saanen', 'especie_id' => $especie->id]);
        $servicio = Servicio::create([
            'clave' => strtoupper(substr(md5(microtime()), 0, 8)),
            'siglas' => 'BRT',
            'descripcion' => 'Brucella Tarjeta',
            'area_id' => ($area ?? $this->area)->id,
        ]);
        $historia = HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => '2026-09-23',
            'fecha_muestra' => '2026-09-22',
            'edad_unidad' => 'NA',
        ]);
        $tipo = TipoMuestra::firstOrCreate(['nombre' => 'SUERO SANGUINEO']);

        return $historia->muestras()->create([
            'servicio_id' => $servicio->id,
            'tipo_muestra_id' => $tipo->id,
            'cantidad' => $cantidad,
        ]);
    }

    /** @param  list<string>  $permisos */
    private function analista(array $permisos = ['diagnosticos.agregar', 'diagnosticos.modificar', 'diagnosticos.emitir', 'diagnosticos.cancelar']): User
    {
        $user = User::factory()->create(['es_admin' => false]);
        foreach ($permisos as $clave) {
            $user->permisosGranulares()->create(['clave' => $clave]);
        }
        $user->areas()->attach($this->area->id);

        return $user;
    }

    /** @return array<string, mixed> */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'accion' => 'guardar',
            'fecha_realizacion' => '2026/10/28',
            'muestras' => [
                ['cantidad' => 3, 'identificacion' => 'SUERO SANGUINEO', 'resultado' => 'negativo', 'aretes' => 'A-1, A-2, A-3'],
                ['cantidad' => 2, 'identificacion' => 'SUERO SANGUINEO', 'resultado' => 'positivo', 'aretes' => ''],
            ],
            'resultado' => '<p>Sin <b>hallazgos</b></p>',
        ], $cambios);
    }

    public function test_muestra_los_datos_fijos_de_la_historia(): void
    {
        $muestra = $this->analisis();

        $props = $this->actingAs($this->analista())
            ->get(route('diagnosticos.edit', $muestra))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('Diagnosticos/Captura', $this->actingAs($this->analista())->get(route('diagnosticos.edit', $muestra))->viewData('page')['component']);
        $this->assertSame('Caprino', $props['analisis']['historia_clinica']['especie']['nombre']);
        $this->assertSame('SUERO SANGUINEO', $props['analisis']['tipo_muestra']['nombre']);
        $this->assertNull($props['diagnostico']);
        $this->assertSame(['guardar' => true, 'emitir' => true, 'cancelar' => true], $props['permisos']);
    }

    public function test_guardar_deja_el_analisis_en_proceso(): void
    {
        $muestra = $this->analisis();
        $user = $this->analista();

        $this->actingAs($user)
            ->put(route('diagnosticos.guardar', $muestra), $this->datos())
            ->assertRedirect(route('diagnosticos.edit', $muestra))
            ->assertSessionHasNoErrors();

        $diagnostico = $muestra->fresh()->diagnostico;
        $this->assertSame(Diagnostico::ESTADO_EN_PROCESO, $diagnostico->estado);
        $this->assertSame('2026-10-28', substr($diagnostico->fecha_realizacion, 0, 10));
        $this->assertNull($diagnostico->fecha_emision);
        $this->assertSame($user->id, $diagnostico->created_by);
        $this->assertSame([3, 2], $diagnostico->muestras->pluck('cantidad')->all());
        $this->assertSame('<p>Sin <b>hallazgos</b></p>', $diagnostico->resultado);
    }

    public function test_guardar_otra_vez_reemplaza_las_muestras(): void
    {
        $muestra = $this->analisis();
        $user = $this->analista();
        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $this->datos());

        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $this->datos([
            'muestras' => [['cantidad' => 1, 'identificacion' => 'SUERO', 'resultado' => 'sospechoso', 'aretes' => '']],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['sospechoso'], $muestra->fresh()->diagnostico->muestras->pluck('resultado')->all());
    }

    public function test_emitir_deja_el_analisis_emitido(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['accion' => 'emitir']))
            ->assertSessionHasNoErrors();

        $diagnostico = $muestra->fresh()->diagnostico;
        $this->assertSame(Diagnostico::ESTADO_EMITIDO, $diagnostico->estado);
        $this->assertNotNull($diagnostico->fecha_emision);
    }

    public function test_no_se_emite_sin_muestras(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['accion' => 'emitir', 'muestras' => []]))
            ->assertSessionHasErrors('muestras');

        $this->assertNull($muestra->fresh()->diagnostico);
    }

    public function test_cancelar_deja_el_analisis_cancelado_aunque_no_tenga_diagnostico(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())
            ->patch(route('diagnosticos.cancelar', $muestra))
            ->assertRedirect(route('diagnosticos.index'));

        $this->assertSame(Diagnostico::ESTADO_CANCELADO, $muestra->fresh()->diagnostico->estado);

        // Ya cancelado, no se puede volver a guardar.
        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos())
            ->assertForbidden();
    }

    public function test_la_fecha_de_realizacion_no_puede_ser_anterior_a_la_de_toma(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['fecha_realizacion' => '2026/09/21']))
            ->assertSessionHasErrors('fecha_realizacion');

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['fecha_realizacion' => '2026/09/22']))
            ->assertSessionHasNoErrors();
    }

    public function test_no_se_capturan_mas_muestras_de_las_que_trae_la_historia(): void
    {
        $muestra = $this->analisis(cantidad: 4);

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos())
            ->assertSessionHasErrors('muestras');

        $this->assertNull($muestra->fresh()->diagnostico);
    }

    public function test_el_resultado_por_muestra_debe_ser_negativo_positivo_o_sospechoso(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())
            ->put(route('diagnosticos.guardar', $muestra), $this->datos([
                'muestras' => [['cantidad' => 1, 'identificacion' => 'SUERO', 'resultado' => 'dudoso', 'aretes' => '']],
            ]))
            ->assertSessionHasErrors('muestras.0.resultado');
    }

    public function test_el_resultado_admite_hasta_520_caracteres_sin_contar_el_formato(): void
    {
        $muestra = $this->analisis();
        $user = $this->analista();

        $this->actingAs($user)
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['resultado' => '<b>'.str_repeat('a', 520).'</b>']))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('diagnosticos.guardar', $muestra), $this->datos(['resultado' => str_repeat('a', 521)]))
            ->assertSessionHasErrors('resultado');
    }

    public function test_el_resultado_se_limpia_de_etiquetas_y_atributos_peligrosos(): void
    {
        $muestra = $this->analisis();

        $this->actingAs($this->analista())->put(route('diagnosticos.guardar', $muestra), $this->datos([
            'resultado' => '<p onclick="x()" style="color:red">Hola <script>alert(1)</script><img src=x onerror=y><b class="c">ok</b></p>',
        ]));

        $this->assertSame('<p>Hola alert(1)<b>ok</b></p>', $muestra->fresh()->diagnostico->resultado);
    }

    public function test_cada_accion_requiere_su_permiso(): void
    {
        $muestra = $this->analisis();

        $soloConsulta = $this->analista(['diagnosticos.consultar_de_otros']);
        $this->actingAs($soloConsulta)->get(route('diagnosticos.edit', $muestra))->assertOk();
        $this->actingAs($soloConsulta)->put(route('diagnosticos.guardar', $muestra), $this->datos())->assertForbidden();
        $this->actingAs($soloConsulta)->put(route('diagnosticos.guardar', $muestra), $this->datos(['accion' => 'emitir']))->assertForbidden();
        $this->actingAs($soloConsulta)->patch(route('diagnosticos.cancelar', $muestra))->assertForbidden();

        // Agregar permite el primer guardado pero no modificar uno en proceso.
        $soloAgregar = $this->analista(['diagnosticos.agregar']);
        $this->actingAs($soloAgregar)->put(route('diagnosticos.guardar', $muestra), $this->datos())->assertRedirect();
        $this->actingAs($soloAgregar)->put(route('diagnosticos.guardar', $muestra), $this->datos())->assertForbidden();
    }

    public function test_un_emitido_solo_se_modifica_con_permiso_de_modificar_emitidos(): void
    {
        $muestra = $this->analisis();
        $user = $this->analista();
        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $this->datos(['accion' => 'emitir']));

        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $this->datos())->assertForbidden();

        $user->permisosGranulares()->create(['clave' => 'diagnosticos.modificar_emitidos']);
        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $this->datos())->assertRedirect();
    }

    public function test_no_se_abre_un_analisis_de_otra_area(): void
    {
        $ajeno = $this->analisis(Area::create(['nombre' => 'Virología']));

        $this->actingAs($this->analista())->get(route('diagnosticos.edit', $ajeno))->assertForbidden();
        $this->actingAs($this->analista())->put(route('diagnosticos.guardar', $ajeno), $this->datos())->assertForbidden();
    }

    public function test_el_diagnostico_de_otro_usuario_requiere_consultar_de_otros(): void
    {
        $muestra = $this->analisis();
        $this->actingAs($this->analista())->put(route('diagnosticos.guardar', $muestra), $this->datos());

        $otro = $this->analista();
        $this->actingAs($otro)->get(route('diagnosticos.edit', $muestra))->assertForbidden();

        $otro->permisosGranulares()->create(['clave' => 'diagnosticos.consultar_de_otros']);
        $this->actingAs($otro)->get(route('diagnosticos.edit', $muestra))->assertOk();
    }

    public function test_si_se_capturan_aretes_debe_haber_uno_por_muestra(): void
    {
        $muestra = $this->analisis();
        $user = $this->analista();
        $fila = fn (string $aretes) => $this->datos([
            'muestras' => [['cantidad' => 3, 'identificacion' => 'SUERO', 'resultado' => 'negativo', 'aretes' => $aretes]],
        ]);

        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $fila('12334 12335'))
            ->assertSessionHasErrors('muestras.0.aretes');

        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $fila('12334, 12335;12336'))
            ->assertSessionHasNoErrors();

        // Sin aretes no se exige nada.
        $this->actingAs($user)->put(route('diagnosticos.guardar', $muestra), $fila(''))
            ->assertSessionHasNoErrors();
    }
}
