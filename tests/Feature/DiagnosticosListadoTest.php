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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listado de diagnósticos: muestra los análisis de las historias clínicas
 * de las áreas asignadas al usuario, inicialmente sólo los pendientes.
 */
class DiagnosticosListadoTest extends TestCase
{
    use RefreshDatabase;

    private function analisis(Area $area, array $historia = [], string $descripcion = 'Coproparasitoscópico'): Muestra
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
        $especie = Especie::firstOrCreate(['nombre' => 'Bovino']);
        $raza = Raza::firstOrCreate(['nombre' => 'Holstein', 'especie_id' => $especie->id]);
        $servicio = Servicio::create([
            'clave' => strtoupper(substr(md5($descripcion.microtime()), 0, 8)),
            'descripcion' => $descripcion,
            'area_id' => $area->id,
        ]);

        $historiaClinica = HistoriaClinica::create(array_merge([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => '2026-05-05',
            'edad_unidad' => 'NA',
        ], $historia));

        return $historiaClinica->muestras()->create(['servicio_id' => $servicio->id, 'cantidad' => 1]);
    }

    private function usuarioConAreas(Area ...$areas): User
    {
        $user = User::factory()->create(['es_admin' => false]);
        $user->permisosGranulares()->create(['clave' => 'diagnosticos.agregar']);
        $user->areas()->attach(collect($areas)->pluck('id'));

        return $user;
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return list<int>
     */
    private function idsListados(User $user, array $filtros = []): array
    {
        $respuesta = $this->actingAs($user)
            ->get(route('diagnosticos.index', $filtros))
            ->assertOk();

        return array_column($respuesta->viewData('page')['props']['analisis']['data'], 'id');
    }

    public function test_requiere_algun_permiso_de_diagnosticos(): void
    {
        $this->actingAs(User::factory()->create(['es_admin' => false]))
            ->get(route('diagnosticos.index'))
            ->assertForbidden();

        $user = User::factory()->create(['es_admin' => false]);
        $user->permisosGranulares()->create(['clave' => 'diagnosticos.consultar_de_otros']);

        $this->actingAs($user)
            ->get(route('diagnosticos.index'))
            ->assertOk();
    }

    public function test_solo_muestra_analisis_de_las_areas_del_usuario(): void
    {
        $bacteriologia = Area::create(['nombre' => 'Bacteriología']);
        $parasitologia = Area::create(['nombre' => 'Parasitología']);
        $propio = $this->analisis($bacteriologia);
        $this->analisis($parasitologia);

        $this->assertSame([$propio->id], $this->idsListados($this->usuarioConAreas($bacteriologia)));
    }

    public function test_un_usuario_sin_areas_no_ve_analisis(): void
    {
        $this->analisis(Area::create(['nombre' => 'Bacteriología']));

        $this->assertSame([], $this->idsListados($this->usuarioConAreas()));
    }

    public function test_el_administrador_ve_todas_las_areas(): void
    {
        $a = $this->analisis(Area::create(['nombre' => 'Bacteriología']));
        $b = $this->analisis(Area::create(['nombre' => 'Parasitología']));

        $ids = $this->idsListados(User::factory()->create(['es_admin' => true]));

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids);
    }

    public function test_sin_filtros_muestra_solo_los_analisis_pendientes(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $pendiente = $this->analisis($area);
        $emitido = $this->analisis($area);
        Diagnostico::create(['muestra_id' => $emitido->id, 'estado' => Diagnostico::ESTADO_EMITIDO, 'fecha_realizacion' => '2026-05-06']);
        $user = $this->usuarioConAreas($area);

        $this->assertSame([$pendiente->id], $this->idsListados($user));
        $this->assertSame(
            Diagnostico::ESTADO_PENDIENTE,
            $this->actingAs($user)->get(route('diagnosticos.index'))->viewData('page')['props']['filtros']['estado'],
        );

        $this->assertSame([$emitido->id], $this->idsListados($user, ['estado' => Diagnostico::ESTADO_EMITIDO]));
        $this->assertEqualsCanonicalizing([$pendiente->id, $emitido->id], $this->idsListados($user, ['estado' => '']));
    }

    public function test_no_lista_analisis_de_historias_canceladas(): void
    {
        $area = Area::create(['nombre' => 'Bacteriología']);
        $vigente = $this->analisis($area);
        $this->analisis($area, ['estado' => HistoriaClinica::ESTADO_CANCELADA]);

        $this->assertSame([$vigente->id], $this->idsListados($this->usuarioConAreas($area), ['estado' => '']));
    }

    public function test_filtra_por_analisis_sin_salirse_de_las_areas_del_usuario(): void
    {
        $area = Area::create(['nombre' => 'Serología']);
        $tarjeta = $this->analisis($area, [], 'Brucella Tarjeta');
        $rivanol = $this->analisis($area, [], 'Brucella Rivanol');
        $ajeno = $this->analisis(Area::create(['nombre' => 'Virología']));
        $user = $this->usuarioConAreas($area);

        $this->assertSame([$rivanol->id], $this->idsListados($user, ['servicio_id' => $rivanol->servicio_id]));

        // Pedir un análisis de un área que no es suya no amplía lo que ve: el filtro se ignora.
        $this->assertEqualsCanonicalizing(
            [$tarjeta->id, $rivanol->id],
            $this->idsListados($user, ['servicio_id' => $ajeno->servicio_id]),
        );
    }

    public function test_filtra_por_numero_de_caso(): void
    {
        $area = Area::create(['nombre' => 'Serología']);
        $this->analisis($area);
        $buscado = $this->analisis($area);
        $user = $this->usuarioConAreas($area);

        $this->assertSame([$buscado->id], $this->idsListados($user, ['numero_caso' => $buscado->historiaClinica->numero_caso]));
    }

    public function test_el_selector_de_analisis_solo_ofrece_los_de_las_areas_del_usuario(): void
    {
        $area = Area::create(['nombre' => 'Serología']);
        $propio = $this->analisis($area);
        $this->analisis(Area::create(['nombre' => 'Virología']));

        $servicios = $this->actingAs($this->usuarioConAreas($area))
            ->get(route('diagnosticos.index'))
            ->viewData('page')['props']['servicios'];

        $this->assertSame([$propio->servicio_id], array_column($servicios, 'id'));
    }

    public function test_filtra_por_rango_de_fechas_de_recepcion(): void
    {
        $area = Area::create(['nombre' => 'Serología']);
        $antes = $this->analisis($area, ['fecha_recepcion' => '2026-10-27 09:00:00']);
        $dentro = $this->analisis($area, ['fecha_recepcion' => '2026-10-29 17:30:00']);
        $despues = $this->analisis($area, ['fecha_recepcion' => '2026-10-31 08:00:00']);
        $user = $this->usuarioConAreas($area);

        $this->assertSame([$dentro->id], $this->idsListados($user, ['fecha_desde' => '2026/10/28', 'fecha_hasta' => '2026/10/30']));
        $this->assertEqualsCanonicalizing([$dentro->id, $despues->id], $this->idsListados($user, ['fecha_desde' => '2026/10/29']));
        $this->assertEqualsCanonicalizing([$antes->id, $dentro->id], $this->idsListados($user, ['fecha_hasta' => '2026/10/29']));

        // Una fecha mal formada se ignora en vez de romper la consulta.
        $this->assertCount(3, $this->idsListados($user, ['fecha_desde' => 'ayer']));
    }
}
