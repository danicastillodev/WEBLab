<?php

namespace Database\Seeders;

use App\Models\Direccion;
use App\Models\Especie;
use App\Models\Explotacion;
use App\Models\FuncionZootecnica;
use App\Models\HistoriaClinica;
use App\Models\Municipio;
use App\Models\Propietario;
use App\Models\Prueba;
use App\Models\Raza;
use App\Models\TipoMuestra;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoriasClinicasMockSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::first()->id;
        $estadoId = 15; // Jalisco

        // ── Propietarios adicionales ──────────────────────────────────────────
        $propietariosData = [
            ['nombre' => 'Carlos',    'apellidos' => 'Ramírez Torres',    'telefono' => '3311112222'],
            ['nombre' => 'María',     'apellidos' => 'González López',    'telefono' => '3322223333'],
            ['nombre' => 'Roberto',   'apellidos' => 'Hernández Méndez',  'telefono' => '3333334444'],
            ['nombre' => 'Alejandra', 'apellidos' => 'Martínez Ruiz',     'telefono' => '3344445555'],
            ['nombre' => 'Jorge',     'apellidos' => 'Flores Vázquez',    'telefono' => '3355556666'],
        ];

        $propietarios = [];
        foreach ($propietariosData as $data) {
            $propietarios[] = Propietario::firstOrCreate(['apellidos' => $data['apellidos'], 'nombre' => $data['nombre']], $data);
        }

        // Incluir propietarios existentes
        $propietarios = array_merge($propietarios, Propietario::whereIn('id', [1, 2])->get()->all());

        // ── Municipios de Jalisco ─────────────────────────────────────────────
        $municipioIds = Municipio::where('estado_id', $estadoId)->inRandomOrder()->limit(10)->pluck('id')->all();

        // ── Direcciones y explotaciones por propietario ───────────────────────
        $direccionesPorPropietario = [];
        $explotacionesPorPropietario = [];

        $calles = ['Av. Juárez', 'Calle Morelos', 'Blvd. Insurgentes', 'Calle Hidalgo', 'Av. Reforma',
            'Calle Zaragoza', 'Calle 5 de Mayo', 'Av. Independencia', 'Calle Aldama', 'Blvd. López Mateos'];
        $colonias = ['Centro', 'San Juan', 'El Refugio', 'La Loma', 'Las Palmas',
            'Santa Cruz', 'El Rosario', 'Las Flores', 'La Esperanza', 'Rancho Nuevo'];
        $explotacionNombres = ['Rancho El Mezquite', 'Granja Los Pinos', 'Rancho La Loma',
            'Granja Santa Rosa', 'Rancho El Potrero', 'Granja San Antonio',
            'Rancho Los Álamos', 'Granja El Carmen'];

        foreach ($propietarios as $i => $propietario) {
            $pid = $propietario->id;

            if (! Direccion::where('propietario_id', $pid)->exists()) {
                $dir = Direccion::create([
                    'propietario_id' => $pid,
                    'calle' => $calles[$i % count($calles)],
                    'numero_exterior' => (string) (100 + $i * 17),
                    'colonia' => $colonias[$i % count($colonias)],
                    'estado_id' => $estadoId,
                    'municipio_id' => $municipioIds[$i % count($municipioIds)],
                    'codigo_postal' => '4'.str_pad($i * 1111, 4, '0', STR_PAD_LEFT),
                ]);
                $direccionesPorPropietario[$pid] = $dir->id;
            } else {
                $direccionesPorPropietario[$pid] = Direccion::where('propietario_id', $pid)->value('id');
            }

            if (! Explotacion::where('propietario_id', $pid)->exists()) {
                $expl = Explotacion::create([
                    'propietario_id' => $pid,
                    'nombre' => $explotacionNombres[$i % count($explotacionNombres)],
                    'estado_id' => $estadoId,
                    'municipio_id' => $municipioIds[$i % count($municipioIds)],
                    'caseta' => 'A'.($i + 1),
                    'lote' => (string) ($i + 1),
                ]);
                $explotacionesPorPropietario[$pid] = $expl->id;
            } else {
                $explotacionesPorPropietario[$pid] = Explotacion::where('propietario_id', $pid)->value('id');
            }
        }

        // ── Datos por especie ─────────────────────────────────────────────────
        $especieDatos = [];
        foreach (Especie::all() as $especie) {
            $razaIds = Raza::where('especie_id', $especie->id)->pluck('id')->all();
            $funcIds = FuncionZootecnica::where('especie_id', $especie->id)->pluck('id')->all();
            $pruebas = Prueba::where('especie_id', $especie->id)->get();

            if ($razaIds && $funcIds && $pruebas->isNotEmpty()) {
                $especieDatos[$especie->id] = [
                    'razas' => $razaIds,
                    'funcs' => $funcIds,
                    'pruebas' => $pruebas,
                ];
            }
        }

        $especieIds = array_keys($especieDatos);
        $sexos = ['Hembra', 'Macho', 'Ambos'];
        $edadUnidades = ['Dias', 'Meses', 'Años'];

        // ── Generar 20 historias ──────────────────────────────────────────────
        $fechaBase = now()->subDays(30);

        for ($n = 0; $n < 20; $n++) {
            $propietario = $propietarios[$n % count($propietarios)];
            $pid = $propietario->id;
            $especieId = $especieIds[$n % count($especieIds)];
            $datos = $especieDatos[$especieId];

            $raza = $datos['razas'][$n % count($datos['razas'])];
            $func = $datos['funcs'][$n % count($datos['funcs'])];
            $sexo = $sexos[$n % count($sexos)];
            $edadUnidad = $edadUnidades[$n % count($edadUnidades)];
            $edadValor = match ($edadUnidad) {
                'Dias' => rand(1, 30),
                'Meses' => rand(1, 24),
                'Años' => rand(1, 12),
            };

            $fechaRecepcion = $fechaBase->copy()->addDays($n)->toDateString();
            $fechaMuestra = $fechaBase->copy()->addDays($n)->subDays(rand(0, 3))->toDateString();

            $historia = DB::transaction(function () use (
                $pid, $especieId, $raza, $func, $sexo, $edadUnidad, $edadValor,
                $fechaRecepcion, $fechaMuestra, $direccionesPorPropietario,
                $explotacionesPorPropietario, $userId
            ) {
                // El número de caso y el ejercicio los asigna el modelo.
                $historia = HistoriaClinica::create([
                    'propietario_id' => $pid,
                    'direccion_id' => $direccionesPorPropietario[$pid],
                    'explotacion_id' => $explotacionesPorPropietario[$pid],
                    'especie_id' => $especieId,
                    'raza_id' => $raza,
                    'funcion_zootecnica_id' => $func,
                    'sexo' => $sexo,
                    'edad_unidad' => $edadUnidad,
                    'edad_valor' => $edadValor,
                    'cantidad' => rand(1, 50),
                    'animales_explotacion' => rand(50, 500),
                    'animales_enfermos' => rand(1, 20),
                    'animales_muertos' => rand(0, 5),
                    'fecha_recepcion' => $fechaRecepcion,
                    'fecha_muestra' => $fechaMuestra,
                    'created_by' => $userId,
                ]);

                return $historia;
            });

            // Agregar 1 o 2 muestras por historia
            $pruebasDisp = $datos['pruebas']->shuffle()->take(rand(1, 2));
            foreach ($pruebasDisp as $prueba) {
                $tipoId = TipoMuestra::where('prueba_id', $prueba->id)->value('id');
                $historia->muestras()->create([
                    'prueba_id' => $prueba->id,
                    'tipo_muestra_id' => $tipoId,
                    'cantidad' => rand(1, 5),
                ]);
            }
        }
    }
}
