<?php

namespace Tests\Feature;

use App\Models\Analista;
use App\Models\Area;
use App\Models\Metodo;
use App\Models\Norma;
use App\Models\Servicio;
use Database\Seeders\ServiciosSistemaAnteriorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosSistemaAnteriorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_los_catalogos_en_una_base_vacia(): void
    {
        ServiciosSistemaAnteriorSeeder::sembrar();

        $this->assertSame(83, Servicio::count());
        $this->assertSame(14, Analista::count());
        $this->assertSame(28, Norma::count());

        $see01 = Servicio::where('clave', 'SEE01')->first();
        $this->assertSame('ELISA', $see01->metodo->nombre);
        $this->assertSame('Serología', $see01->area->nombre);
        $this->assertSame('MVZ NOEMI SANDOVAL PARRA', $see01->analista->nombre);
        $this->assertEqualsCanonicalizing(
            ['NOM-007-ZOO-1994', 'NOM-056 / EA'],
            $see01->normas->pluck('nombre')->all()
        );

        // Claves renombradas en el catálogo actual.
        $this->assertTrue(Servicio::where('clave', 'SEP04')->exists());
        $this->assertFalse(Servicio::where('clave', 'SEE04')->exists());
    }

    public function test_respeta_lo_que_ya_existe_y_solo_completa_vacios(): void
    {
        $area = Area::create(['nombre' => 'Serología']);
        $metodo = Metodo::create(['nombre' => 'INMUNODIFUSION DOBLE']);
        $servicio = Servicio::create([
            'clave' => 'SEE02',
            'siglas' => 'PRRSE',
            'descripcion' => 'PRRS X3/ELISA',
            'area_id' => $area->id,
            'activo' => false,
        ]);

        ServiciosSistemaAnteriorSeeder::sembrar();
        ServiciosSistemaAnteriorSeeder::sembrar();

        $servicio->refresh();
        $this->assertSame('PRRS X3/ELISA', $servicio->descripcion);
        $this->assertFalse($servicio->activo);
        $this->assertSame('DETECCION DE ANTICUERPOS CONTRA PRRS/ELISA', $servicio->nombre_analisis);
        $this->assertNotNull($servicio->analista_id);

        $this->assertSame(1, Area::where('nombre', 'Serología')->count());
        $this->assertSame(1, Metodo::where('nombre', 'like', 'INMUNODIFUSION DOBLE%')->count());
        $this->assertSame(83, Servicio::count());
    }
}
