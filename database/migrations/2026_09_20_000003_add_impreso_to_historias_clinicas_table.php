<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra si el resultado de la historia clínica ya se imprimió. Nace en
     * "no": nada se ha impreso todavía cuando se da de alta el caso.
     */
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->enum('impreso', ['si', 'no', 'parcial'])
                ->default('no')
                ->after('estado');

            $table->index('impreso');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropIndex(['impreso']);
            $table->dropColumn('impreso');
        });
    }
};
