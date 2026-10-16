<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuncionesZootecnicasSeeder extends Seeder
{
    /**
     * Catálogo de funciones zootécnicas del sistema anterior, con su id
     * original. Se conservan tal cual, variantes y erratas incluidas
     * ("CICLO CLOMPLETO - TRASPATIO", "ENGORDADOR."), porque así quedaron
     * capturadas en las historias de entonces.
     */
    public const CATALOGO = [
        2 => 'NR',
        3 => 'LECHE',
        4 => 'CICLO COMPLETO',
        5 => 'TRASPATIO',
        6 => 'ENGORDA-NUCLEO',
        9 => 'PRODUCCION',
        11 => 'CENTRO INSEMINACION ARTIFICIAL',
        12 => 'LECHONERA',
        18 => 'BANCO DE SEMEN',
        20 => 'MULTIPLICADORA',
        21 => 'PIE DE CRIA',
        23 => 'ORNATO',
        24 => 'COMBATE',
        25 => 'MIXTO',
        27 => 'CARNE',
        30 => 'RECREACION',
        31 => 'PRODUCCION DE CARNE',
        32 => 'EXHIBICION',
        33 => 'NUCLEO 1',
        34 => 'CRIA',
        35 => 'COMPAÑIA',
        36 => 'CAZA',
        37 => 'MENSAJERAS',
        38 => 'PONEDERAS',
        39 => 'REPRODUCTORA',
        40 => 'MASCOTA',
        41 => 'DOBLE PROPOSITO',
        42 => 'RASTRO',
        43 => 'CICLO CLOMPLETO - TRASPATIO',
        45 => 'DEPORTE',
        46 => 'SEMITECNIFICADO',
        47 => 'CICLO COMPLETO-TRAS',
        48 => 'CICLO COMPLETO-TRASP',
        49 => 'PRODUCCION DE LECHE',
        50 => 'LIDIA',
        51 => 'CINEGETICA',
        54 => 'HEMBRAS DE DESECHO',
        55 => 'PRUEBAS BIOLOGICAS',
        56 => 'ENGORDADOR.',
        57 => 'SITIO 1',
        58 => 'EQUITACION',
        59 => 'GENETICA',
        60 => 'SALTO',
        61 => 'PONEDORAS',
        62 => 'COMBATE-ORNATO',
        63 => 'INVESTIGACION',
        64 => 'REEMPLAZOS',
        65 => 'REPARO',
        66 => 'PRODUCCION DE HUEVO FERTIL',
        67 => 'ENGORDA-TRASPATIO',
        68 => 'ENGORDADOR TRASPATIO',
        69 => 'CUARENTENA',
        70 => 'SITIO 2',
        74 => 'NACEDORA',
        75 => 'PIE DE CRIA-ENGORDA',
        76 => 'LECHONERA-TRASPATIO',
        78 => 'POSTA DE SEMENTALES',
        79 => 'DESTETE',
        80 => 'NUCLEO',
        81 => 'SEMENTAL',
        82 => 'ORNATO/COMBATE',
        83 => 'ENGORDA',
        84 => 'ESPECTACULO',
        85 => 'POSTURA',
        86 => 'NUCLEO/ENGORDA',
        88 => 'CENTRO DE INSEMINACION',
        89 => 'EXPERIMENTAL',
        90 => 'REPRODUCCION',
        96 => 'MULTIPLICADORA CICLO COMPLETO',
        98 => 'RASTRO MUNICIPAL',
        99 => 'TECNIFICADO',
        100 => 'MASCOTA Y CARNE',
    ];

    private const RUMIANTES = ['Bovinos', 'Búfalo', 'Caprinos', 'Ovinos', 'Ovinos y caprinos'];

    private const ABASTO = [...self::RUMIANTES, 'Porcinos', 'Aves'];

    private const REPRODUCTORES = [...self::RUMIANTES, 'Porcinos', 'Equinos'];

    private const COMPANIA = ['Canino', 'Felino', 'Conejo', 'Cobayo', 'Hurón', 'Roedor', 'Aves', 'Reptil', 'Pez', 'Exótico'];

    private const FAUNA = ['Ciervo', 'Venado', 'Antílope', 'Oryx cimitarra', 'Faisán', 'Aves'];

    private const LABORATORIO = ['Roedor', 'Conejo', 'Cobayo', 'Rana', 'Primate', 'Porcinos', 'Aves', 'Bovinos', 'Caprinos', 'Ovinos', 'Pez', 'Canino', 'Felino'];

    /**
     * A qué especies aplica cada función. Es una propuesta razonada a partir
     * del nombre (el sistema anterior no lo registraba): las propias de la
     * porcicultura (lechonera, sitios, núcleos, ciclo completo) solo a
     * porcinos, las de postura y combate a aves, las ecuestres a equinos,
     * etc. Se ajusta desde Catálogos → Especies o → Funciones zootécnicas.
     * NR ("no registrada") aplica a todas.
     *
     * @return array<string, list<string>>
     */
    public static function especiesPorFuncion(): array
    {
        $porcinos = ['Porcinos'];
        $aves = ['Aves'];
        $equinos = ['Equinos'];

        return [
            'LECHE' => self::RUMIANTES,
            'PRODUCCION DE LECHE' => self::RUMIANTES,
            'CICLO COMPLETO' => $porcinos,
            'CICLO CLOMPLETO - TRASPATIO' => $porcinos,
            'CICLO COMPLETO-TRAS' => $porcinos,
            'CICLO COMPLETO-TRASP' => $porcinos,
            'TRASPATIO' => [...self::ABASTO, 'Conejo', 'Faisán'],
            'ENGORDA-NUCLEO' => $porcinos,
            'LECHONERA' => $porcinos,
            'LECHONERA-TRASPATIO' => $porcinos,
            'SITIO 1' => $porcinos,
            'SITIO 2' => $porcinos,
            'NUCLEO' => $porcinos,
            'NUCLEO 1' => $porcinos,
            'NUCLEO/ENGORDA' => $porcinos,
            'MULTIPLICADORA CICLO COMPLETO' => $porcinos,
            'DESTETE' => ['Porcinos', 'Bovinos'],
            'HEMBRAS DE DESECHO' => ['Porcinos', 'Bovinos'],
            'REEMPLAZOS' => ['Porcinos', 'Bovinos', 'Aves'],
            'MULTIPLICADORA' => ['Porcinos', 'Aves'],
            'PRODUCCION' => [...self::ABASTO, 'Conejo', 'Pez'],
            'CENTRO INSEMINACION ARTIFICIAL' => self::REPRODUCTORES,
            'CENTRO DE INSEMINACION' => self::REPRODUCTORES,
            'BANCO DE SEMEN' => self::REPRODUCTORES,
            'POSTA DE SEMENTALES' => [...self::REPRODUCTORES, 'Asno', 'Burro'],
            'SEMENTAL' => [...self::REPRODUCTORES, 'Asno', 'Burro'],
            'PIE DE CRIA' => [...self::REPRODUCTORES, 'Aves', 'Conejo', 'Ciervo', 'Venado'],
            'PIE DE CRIA-ENGORDA' => ['Porcinos', 'Bovinos'],
            'GENETICA' => [...self::REPRODUCTORES, 'Aves'],
            'REPRODUCCION' => [...self::REPRODUCTORES, 'Aves', 'Canino', 'Felino', 'Conejo'],
            'REPRODUCTORA' => ['Aves', 'Porcinos'],
            'CRIA' => [...self::REPRODUCTORES, 'Aves', 'Canino', 'Felino', 'Conejo'],
            'ORNATO' => ['Aves', 'Faisán', 'Cisne', 'Pez'],
            'COMBATE' => $aves,
            'ORNATO/COMBATE' => $aves,
            'COMBATE-ORNATO' => $aves,
            'MENSAJERAS' => $aves,
            'PONEDERAS' => $aves,
            'PONEDORAS' => $aves,
            'POSTURA' => $aves,
            'PRODUCCION DE HUEVO FERTIL' => $aves,
            'NACEDORA' => $aves,
            'MIXTO' => [...self::RUMIANTES, 'Aves'],
            'DOBLE PROPOSITO' => [...self::RUMIANTES, 'Aves'],
            'CARNE' => [...self::ABASTO, 'Conejo', 'Pez', 'Venado', 'Ciervo', 'Faisán'],
            'PRODUCCION DE CARNE' => [...self::ABASTO, 'Conejo', 'Pez', 'Venado', 'Ciervo', 'Faisán'],
            'MASCOTA Y CARNE' => ['Conejo', 'Cobayo', 'Aves', 'Caprinos', 'Ovinos', 'Porcinos'],
            'ENGORDA' => [...self::ABASTO, 'Conejo'],
            'ENGORDADOR.' => ['Porcinos', 'Bovinos'],
            'ENGORDA-TRASPATIO' => ['Porcinos', 'Aves'],
            'ENGORDADOR TRASPATIO' => ['Porcinos', 'Aves'],
            'RASTRO' => [...self::ABASTO, 'Equinos'],
            'RASTRO MUNICIPAL' => [...self::ABASTO, 'Equinos'],
            'SEMITECNIFICADO' => [...self::ABASTO, 'Conejo', 'Pez'],
            'TECNIFICADO' => [...self::ABASTO, 'Conejo', 'Pez'],
            'LIDIA' => ['Bovinos'],
            'REPARO' => ['Bovinos'],
            'DEPORTE' => [...$equinos, 'Canino'],
            'EQUITACION' => $equinos,
            'SALTO' => $equinos,
            'RECREACION' => ['Equinos', 'Asno', 'Burro', 'Canino'],
            'EXHIBICION' => ['Equinos', 'Bovinos', 'Caprinos', 'Ovinos', 'Aves', 'Canino', 'Felino', 'Exótico', 'Antílope', 'Oryx cimitarra', 'Canguro', 'Hiénido', 'Primate', 'Reptil', 'Zorro', 'Cisne', 'Faisán', 'Venado', 'Ciervo'],
            'ESPECTACULO' => ['Equinos', 'Bovinos', 'Aves', 'Canino', 'Felino', 'Exótico', 'Primate', 'Reptil', 'Hiénido', 'Canguro'],
            'COMPAÑIA' => self::COMPANIA,
            'MASCOTA' => [...self::COMPANIA, 'Equinos', 'Asno', 'Burro', 'Rana'],
            'CAZA' => [...self::FAUNA, 'Canino'],
            'CINEGETICA' => self::FAUNA,
            'INVESTIGACION' => self::LABORATORIO,
            'EXPERIMENTAL' => self::LABORATORIO,
            'PRUEBAS BIOLOGICAS' => ['Roedor', 'Conejo', 'Cobayo', 'Aves', 'Rana'],
            'CUARENTENA' => [...self::ABASTO, 'Equinos', 'Canino', 'Felino', 'Exótico'],
        ];
    }

    public function run(): void
    {
        self::sembrar();
    }

    /**
     * Inserta las funciones que falten (por id) y los enlaces con especies
     * que falten. Es idempotente y no quita enlaces que se hayan agregado a
     * mano desde el catálogo.
     */
    public static function sembrar(): void
    {
        $now = now();
        $existentes = DB::table('funcion_zootecnicas')->pluck('id')->flip();
        $nuevas = collect(self::CATALOGO)
            ->reject(fn ($nombre, $id) => $existentes->has($id))
            ->map(fn ($nombre, $id) => ['id' => $id, 'nombre' => $nombre, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all();
        DB::table('funcion_zootecnicas')->insert($nuevas);

        $especies = DB::table('especies')->pluck('id', 'nombre');
        $funciones = DB::table('funcion_zootecnicas')->whereIn('id', array_keys(self::CATALOGO))->pluck('id', 'nombre');

        $enlaces = [];
        foreach ($funciones as $nombre => $funcionId) {
            $aplica = $nombre === 'NR' ? $especies->keys()->all() : (self::especiesPorFuncion()[$nombre] ?? []);
            foreach (array_unique($aplica) as $especie) {
                if ($especies->has($especie)) {
                    $enlaces[] = ['especie_id' => $especies[$especie], 'funcion_zootecnica_id' => $funcionId];
                }
            }
        }
        foreach (array_chunk($enlaces, 500) as $lote) {
            DB::table('especie_funcion_zootecnica')->insertOrIgnore($lote);
        }
    }
}
