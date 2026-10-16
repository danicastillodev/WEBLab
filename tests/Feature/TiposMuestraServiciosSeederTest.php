<?php

namespace Tests\Feature;

use App\Models\Servicio;
use App\Models\TipoMuestra;
use Database\Seeders\ServiciosSistemaAnteriorSeeder;
use Database\Seeders\TiposMuestraServiciosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiposMuestraServiciosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reemplaza_los_tipos_y_los_liga_a_los_servicios(): void
    {
        ServiciosSistemaAnteriorSeeder::sembrar();
        $ejemplo = TipoMuestra::create(['nombre' => 'RESIDUOS BIOLÓGICOS', 'prueba_id' => null]);
        $suero = TipoMuestra::create(['nombre' => 'Suero sanguíneo', 'prueba_id' => null]);

        TiposMuestraServiciosSeeder::sembrar();
        TiposMuestraServiciosSeeder::sembrar();

        $this->assertSame(60, TipoMuestra::count());
        $this->assertModelMissing($ejemplo);

        // El que coincide sin acentos conserva su id con el nombre anterior.
        $this->assertSame('SUERO SANGUINEO', $suero->refresh()->nombre);

        $see01 = Servicio::where('clave', 'SEE01')->first();
        $this->assertContains('SUERO SANGUINEO', $see01->tiposMuestra->pluck('nombre'));

        // La clave renombrada también recibe sus tipos.
        $this->assertNotEmpty(Servicio::where('clave', 'SEP04')->first()->tiposMuestra);
    }
}
