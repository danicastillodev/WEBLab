<?php

namespace Tests\Feature;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Estado;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Raza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valores que el sistema asigna al dar de alta una historia clínica: el
 * ejercicio (el año de la fecha de recepción), el número de caso —consecutivo
 * dentro de ese ejercicio, reinicia cada año: 2025 → 1, 2, 3…; 2026 → 1, 2, 3…—
 * y el estado de impresión del resultado.
 */
class HistoriaClinicaAltaTest extends TestCase
{
    use RefreshDatabase;

    private function historia(string $fechaRecepcion): HistoriaClinica
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

        return HistoriaClinica::create([
            'propietario_id' => $propietario->id,
            'direccion_id' => $direccion->id,
            'especie_id' => $especie->id,
            'raza_id' => $raza->id,
            'fecha_recepcion' => $fechaRecepcion,
            'edad_unidad' => 'NA',
        ]);
    }

    public function test_una_historia_nueva_nace_sin_imprimir(): void
    {
        $this->assertSame(HistoriaClinica::IMPRESO_NO, $this->historia('2026-10-10')->fresh()->impreso);
    }

    public function test_el_ejercicio_sale_del_anio_de_la_fecha_de_recepcion(): void
    {
        $this->assertSame(2026, $this->historia('2026-10-10')->ejercicio);
    }

    public function test_el_numero_de_caso_es_consecutivo_dentro_del_ejercicio(): void
    {
        $this->assertSame(1, $this->historia('2026-01-15')->numero_caso);
        $this->assertSame(2, $this->historia('2026-03-04')->numero_caso);
        $this->assertSame(3, $this->historia('2026-11-30')->numero_caso);
    }

    public function test_el_consecutivo_reinicia_en_cada_ejercicio(): void
    {
        $this->historia('2025-05-01');
        $this->historia('2025-06-01');

        $primeraDe2026 = $this->historia('2026-01-02');

        $this->assertSame(2026, $primeraDe2026->ejercicio);
        $this->assertSame(1, $primeraDe2026->numero_caso);

        $terceraDe2025 = $this->historia('2025-12-31');

        $this->assertSame(2025, $terceraDe2025->ejercicio);
        $this->assertSame(3, $terceraDe2025->numero_caso);
    }
}
