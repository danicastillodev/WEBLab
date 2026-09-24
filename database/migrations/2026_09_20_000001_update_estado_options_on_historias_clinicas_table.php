<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El catálogo de estados pasa a ser: pendiente, en proceso, cancelada,
     * concluida y resultado parcial. "completada" se renombra a "concluida",
     * así que se ensancha el enum, se migran los registros y se vuelve a
     * cerrar sobre el catálogo definitivo.
     */
    public function up(): void
    {
        $this->ensanchar(['pendiente', 'en_proceso', 'completada', 'concluida', 'cancelada', 'resultado_parcial']);

        DB::table('historias_clinicas')->where('estado', 'completada')->update(['estado' => 'concluida']);

        $this->ensanchar(['pendiente', 'en_proceso', 'concluida', 'cancelada', 'resultado_parcial']);
    }

    public function down(): void
    {
        $this->ensanchar(['pendiente', 'en_proceso', 'completada', 'concluida', 'cancelada', 'resultado_parcial']);

        DB::table('historias_clinicas')->where('estado', 'concluida')->update(['estado' => 'completada']);
        DB::table('historias_clinicas')->where('estado', 'resultado_parcial')->update(['estado' => 'en_proceso']);

        $this->ensanchar(['pendiente', 'en_proceso', 'completada', 'cancelada']);
    }

    /**
     * @param  list<string>  $opciones
     */
    private function ensanchar(array $opciones): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) use ($opciones) {
            $table->enum('estado', $opciones)->default('pendiente')->change();
        });
    }
};
