<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->string('texto', 500);
            $table->enum('tipo', ['texto', 'si_no', 'numero'])->default('texto');
            $table->unsignedSmallInteger('orden')->default(0);
            // Permite retirar una pregunta del formulario sin borrar las
            // respuestas históricas que ya se capturaron con ella.
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['activa', 'orden']);
        });

        $now = now();
        DB::table('preguntas')->insert([
            ['texto' => '¿Qué control sanitario emplean?',                            'tipo' => 'texto',  'orden' => 1, 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
            ['texto' => '¿El problema existe en granjas contiguas?',                  'tipo' => 'si_no',  'orden' => 2, 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
            ['texto' => '¿A qué distancia se encuentran de ellas?',                   'tipo' => 'numero', 'orden' => 3, 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
            ['texto' => '¿En qué condiciones es almacenado el alimento que consumen?', 'tipo' => 'texto', 'orden' => 4, 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
            ['texto' => '¿Cuál es la procedencia del agua que se consume?',           'tipo' => 'texto',  'orden' => 5, 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
