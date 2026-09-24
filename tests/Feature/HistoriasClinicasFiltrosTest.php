<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtros del listado de historias clínicas: ejercicio, estado e impreso.
 * El listado también muestra quién registró cada historia, así que la relación
 * tiene que llegar cargada a la vista.
 */
class HistoriasClinicasFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private function historia(array $atributos = []): HistoriaClinica
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

        return HistoriaClinica::create(array_merge([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => '2026-05-05',
            'edad_unidad' => 'NA',
        ], $atributos));
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return list<int>
     */
    private function idsListados(array $filtros): array
    {
        $respuesta = $this->actingAs(User::factory()->create(['es_admin' => true]))
            ->get(route('historias-clinicas.index', $filtros))
            ->assertOk();

        $historias = $respuesta->viewData('page')['props']['historias']['data'];

        return array_column($historias, 'id');
    }

    public function test_filtra_por_ejercicio(): void
    {
        $de2025 = $this->historia(['fecha_recepcion' => '2025-05-05']);
        $de2026 = $this->historia(['fecha_recepcion' => '2026-05-05']);

        $this->assertSame([$de2025->id], $this->idsListados(['ejercicio' => 2025]));
        $this->assertSame([$de2026->id], $this->idsListados(['ejercicio' => 2026]));
    }

    public function test_filtra_por_estado(): void
    {
        $pendiente = $this->historia();
        $concluida = $this->historia(['estado' => HistoriaClinica::ESTADO_CONCLUIDA]);

        $this->assertSame([$pendiente->id], $this->idsListados(['estado' => HistoriaClinica::ESTADO_PENDIENTE]));
        $this->assertSame([$concluida->id], $this->idsListados(['estado' => HistoriaClinica::ESTADO_CONCLUIDA]));
    }

    public function test_filtra_por_impreso(): void
    {
        $sinImprimir = $this->historia();
        $parcial = $this->historia(['impreso' => HistoriaClinica::IMPRESO_PARCIAL]);

        $this->assertSame([$sinImprimir->id], $this->idsListados(['impreso' => HistoriaClinica::IMPRESO_NO]));
        $this->assertSame([$parcial->id], $this->idsListados(['impreso' => HistoriaClinica::IMPRESO_PARCIAL]));
    }

    /**
     * Un valor fuera del catálogo se ignora en vez de vaciar el listado.
     */
    public function test_ignora_un_estado_que_no_existe(): void
    {
        $historia = $this->historia();

        $this->assertSame([$historia->id], $this->idsListados(['estado' => 'inventado']));
    }

    public function test_el_listado_incluye_quien_registro_la_historia(): void
    {
        $usuario = User::factory()->create(['es_admin' => true, 'name' => 'Lupita Ramírez']);

        $this->actingAs($usuario);
        $this->historia();

        $respuesta = $this->actingAs($usuario)
            ->get(route('historias-clinicas.index', ['ejercicio' => 2026]))
            ->assertOk();

        $historias = $respuesta->viewData('page')['props']['historias']['data'];

        $this->assertSame('Lupita Ramírez', $historias[0]['creado_por']['name']);
    }

    /**
     * Sin parámetros, la primera visita redirige con los valores por defecto:
     * hoy, el ejercicio en curso y "No" en impreso.
     */
    public function test_la_primera_visita_redirige_con_impreso_no_por_defecto(): void
    {
        $respuesta = $this->actingAs(User::factory()->create(['es_admin' => true]))
            ->get(route('historias-clinicas.index'));

        $respuesta->assertRedirect();
        parse_str(parse_url($respuesta->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('no', $query['impreso'] ?? null);
    }
}
