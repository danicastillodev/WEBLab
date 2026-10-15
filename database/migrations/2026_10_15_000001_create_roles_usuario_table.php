<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles como plantillas de permisos (ver App\Models\Rol). Se crean aquí,
     * y no en un seeder, para que los cuatro roles predefinidos lleguen
     * también a producción con el despliegue. Después pueden editarse o
     * eliminarse desde Seguridad > Roles.
     */
    public function up(): void
    {
        Schema::create('roles_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            // Matriz módulo => {ver, crear, editar, eliminar}, sólo filas con algo concedido.
            $table->json('permisos')->nullable();
            // Lista de claves de config/permisos_granulares.php concedidas.
            $table->json('permisos_granulares')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('es_admin')
                ->constrained('roles_usuario')->nullOnDelete();
        });

        $ahora = now();

        foreach ($this->rolesPredefinidos() as $nombre => $rol) {
            DB::table('roles_usuario')->insert([
                'nombre' => $nombre,
                'descripcion' => $rol['descripcion'],
                'permisos' => json_encode($rol['permisos']),
                'permisos_granulares' => json_encode($rol['permisos_granulares']),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });

        Schema::dropIfExists('roles_usuario');
    }

    /**
     * @return array<string, array{descripcion: string, permisos: array, permisos_granulares: array}>
     */
    private function rolesPredefinidos(): array
    {
        $ver = ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false];
        $verCrearEditar = ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false];
        $todo = ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => true];

        $catalogosDeConsulta = [
            'municipios' => $ver, 'estados' => $ver, 'especies' => $ver,
            'razas' => $ver, 'funcion-zootecnicas' => $ver,
        ];

        return [
            'Recepción' => [
                'descripcion' => 'Registro de historias clínicas, propietarios y notas de pago.',
                'permisos' => [
                    'historias-clinicas' => $verCrearEditar,
                    'cuestionario' => $ver,
                    'propietarios' => $verCrearEditar,
                    'productos' => $ver,
                    'servicios' => $ver,
                    ...$catalogosDeConsulta,
                ],
                'permisos_granulares' => [
                    'facturacion.agregar_facturas',
                    'facturacion.agregar_notas_abono',
                    'historias-clinicas.imprimir_resultado',
                ],
            ],

            'Análisis' => [
                'descripcion' => 'Captura de resultados y diagnósticos de las muestras.',
                'permisos' => [
                    'historias-clinicas' => ['ver' => true, 'crear' => false, 'editar' => true, 'eliminar' => false],
                    'cuestionario' => $ver,
                    'pruebas' => $ver,
                    'metodos' => $ver,
                    'especies' => $ver,
                    'razas' => $ver,
                ],
                'permisos_granulares' => [
                    'diagnosticos.agregar',
                    'diagnosticos.modificar',
                    'diagnosticos.emitir',
                    'historias-clinicas.imprimir_resultado',
                    'reportes.diagnosticos',
                    'reportes.analisis_x_especie',
                    'reportes.muestras_trabajadas_x_especie',
                    'reportes.volumen_trabajo',
                ],
            ],

            'Auxiliar' => [
                'descripcion' => 'Apoyo en laboratorio: consulta de historias y captura de diagnósticos.',
                'permisos' => [
                    'historias-clinicas' => $ver,
                    'cuestionario' => $ver,
                    'pruebas' => $ver,
                    'metodos' => $ver,
                    'especies' => $ver,
                    'razas' => $ver,
                ],
                'permisos_granulares' => [
                    'diagnosticos.agregar',
                ],
            ],

            // Dirección parte con acceso total a todo lo declarado hoy.
            'Dirección' => [
                'descripcion' => 'Acceso completo a todas las funciones del sistema.',
                'permisos' => collect(array_keys(config('modulos.modulos')))
                    ->mapWithKeys(fn (string $modulo) => [$modulo => $todo])
                    ->all(),
                'permisos_granulares' => collect(config('permisos_granulares.grupos'))
                    ->flatMap(fn (array $grupo) => array_keys($grupo['permisos']))
                    ->values()
                    ->all(),
            ],
        ];
    }
};
