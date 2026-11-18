<?php

namespace Tests\Feature;

use App\Models\Diagnostico;
use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use App\Models\Servicio;
use App\Models\TipoMuestra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActualizarEstadosHistoriasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Historia con un análisis por cada estado de diagnóstico dado (null =
     * sin diagnóstico), creada directo en la base, como las ya existentes.
     *
     * @param  list<?string>  $estados
     */
    private function historia(array $estados, string $estado = HistoriaClinica::ESTADO_PENDIENTE): HistoriaClinica
    {
        $propietario = Propietario::create(['nombre' => 'Ana', 'apellidos' => 'Pérez']);
        $estadoGeo = Estado::where('clave', 'JAL')->firstOrFail();
        $municipio = Municipio::firstOrCreate(['nombre' => 'Guadalajara', 'estado_id' => $estadoGeo->id]);
        $direccion = Direccion::create([
            'propietario_id' => $propietario->id, 'calle' => 'Av. Central',
            'numero_exterior' => '10', 'colonia' => 'Centro',
            'municipio_id' => $municipio->id, 'estado_id' => $estadoGeo->id,
            'codigo_postal' => '44100',
        ]);
        $especie = Especie::firstOrCreate(['nombre' => 'Caprino']);
        $raza = Raza::firstOrCreate(['nombre' => 'Saanen', 'especie_id' => $especie->id]);
        $historia = HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => '2026-09-23',
            'fecha_muestra' => '2026-09-22',
            'edad_unidad' => 'NA',
        ]);
        $historia->update(['estado' => $estado]);
        $tipo = TipoMuestra::firstOrCreate(['nombre' => 'SUERO SANGUINEO']);

        foreach ($estados as $estadoDiagnostico) {
            $servicio = Servicio::create([
                'clave' => strtoupper(substr(md5(microtime().rand()), 0, 8)),
                'siglas' => 'BRT',
                'descripcion' => 'Brucella Tarjeta',
            ]);
            $muestra = $historia->muestras()->create([
                'servicio_id' => $servicio->id,
                'tipo_muestra_id' => $tipo->id,
                'cantidad' => 5,
            ]);
            if ($estadoDiagnostico) {
                Diagnostico::create(['muestra_id' => $muestra->id, 'estado' => $estadoDiagnostico]);
            }
        }

        return $historia;
    }

    public function test_recalcula_el_estado_de_las_historias_existentes(): void
    {
        $concluida = $this->historia([Diagnostico::ESTADO_EMITIDO, Diagnostico::ESTADO_CANCELADO]);
        $parcial = $this->historia([Diagnostico::ESTADO_EMITIDO, null]);
        $enProceso = $this->historia([Diagnostico::ESTADO_EN_PROCESO]);
        $sinDiagnosticos = $this->historia([null]);
        $cancelada = $this->historia([Diagnostico::ESTADO_EMITIDO], HistoriaClinica::ESTADO_CANCELADA);

        $this->artisan('weblab:actualizar-estados-historias')
            ->expectsOutputToContain('Actualizadas: 3.')
            ->assertSuccessful();

        $this->assertSame(HistoriaClinica::ESTADO_CONCLUIDA, $concluida->fresh()->estado);
        $this->assertSame(HistoriaClinica::ESTADO_RESULTADO_PARCIAL, $parcial->fresh()->estado);
        $this->assertSame(HistoriaClinica::ESTADO_RESULTADO_PARCIAL, $enProceso->fresh()->estado);
        $this->assertSame(HistoriaClinica::ESTADO_PENDIENTE, $sinDiagnosticos->fresh()->estado);
        $this->assertSame(HistoriaClinica::ESTADO_CANCELADA, $cancelada->fresh()->estado);
    }
}
