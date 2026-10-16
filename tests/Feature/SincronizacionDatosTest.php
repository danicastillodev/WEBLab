<?php

namespace Tests\Feature;

use App\Console\Commands\DatosSincronizables;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SincronizacionDatosTest extends TestCase
{
    use RefreshDatabase;

    private string $respaldo;

    protected function setUp(): void
    {
        parent::setUp();
        // El test escribe en database/data/sync: se respalda
        // lo que haya para dejarlo como estaba.
        $this->respaldo = sys_get_temp_dir().'/sync_respaldo_'.uniqid();
        if (File::isDirectory(DatosSincronizables::directorio())) {
            File::copyDirectory(DatosSincronizables::directorio(), $this->respaldo);
        }
        File::delete(storage_path('app/datos_sync_firma'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(DatosSincronizables::directorio());
        if (File::isDirectory($this->respaldo)) {
            File::moveDirectory($this->respaldo, DatosSincronizables::directorio());
        }
        File::delete(storage_path('app/datos_sync_firma'));
        parent::tearDown();
    }

    public function test_no_exporta_seguridad_historias_ni_notas_de_pago(): void
    {
        foreach (['users', 'roles_usuario', 'user_permissions', 'user_permisos_granulares', 'area_user',
            'sessions', 'historias_clinicas', 'muestras', 'respuestas',
            'notas_pago', 'nota_pago_conceptos', 'nota_pago_historia_clinica'] as $tabla) {
            $this->assertNotContains($tabla, DatosSincronizables::TABLAS);
        }
    }

    public function test_importa_y_conserva_lo_que_esta_en_uso(): void
    {
        $estado = DB::table('estados')->insertGetId(['nombre' => 'Estado local', 'clave' => 'T1', 'created_at' => now(), 'updated_at' => now()]);
        $usuario = User::factory()->create();

        $this->artisan('datos:exportar', ['--sin-migracion' => true])->assertSuccessful();

        // En el "servidor": un estado de más sin uso, otro cambiado de nombre,
        // y otro de más con un municipio que tampoco existe en local.
        DB::table('estados')->where('id', $estado)->update(['nombre' => 'Cambiado']);
        $sobrante = DB::table('estados')->insertGetId(['nombre' => 'Sobrante', 'clave' => 'T2', 'created_at' => now(), 'updated_at' => now()]);
        $enUso = DB::table('estados')->insertGetId(['nombre' => 'En uso', 'clave' => 'T3', 'created_at' => now(), 'updated_at' => now()]);
        $municipio = DB::table('municipios')->insertGetId(['nombre' => 'X', 'estado_id' => $enUso, 'created_at' => now(), 'updated_at' => now()]);
        // Un área que solo existe en el servidor pero tiene usuarios asignados:
        // borrarla se llevaría en cascada su relación con users.
        $area = DB::table('areas')->insertGetId(['nombre' => 'Solo servidor', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('area_user')->insert(['area_id' => $area, 'user_id' => $usuario->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('datos:importar')->assertSuccessful();

        $this->assertDatabaseHas('estados', ['id' => $estado, 'nombre' => 'Estado local']);
        $this->assertDatabaseMissing('estados', ['id' => $sobrante]);
        // El municipio sobrante se va primero, y con eso su estado queda libre.
        $this->assertDatabaseMissing('municipios', ['id' => $municipio]);
        $this->assertDatabaseMissing('estados', ['id' => $enUso]);
        $this->assertDatabaseHas('areas', ['id' => $area]);
        $this->assertDatabaseHas('area_user', ['area_id' => $area, 'user_id' => $usuario->id]);

        // Un segundo import con el mismo snapshot no hace nada.
        DB::table('estados')->where('id', $estado)->update(['nombre' => 'Otra vez']);
        $this->artisan('datos:importar')->expectsOutputToContain('ya estaba aplicado')->assertSuccessful();
        $this->assertDatabaseHas('estados', ['id' => $estado, 'nombre' => 'Otra vez']);
    }
}
