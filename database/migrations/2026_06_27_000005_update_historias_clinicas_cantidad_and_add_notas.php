<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable()->change();
            $table->string('notas_adicionales', 512)->nullable()->after('animales_enfermos');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->nullable(false)->change();
            $table->dropColumn('notas_adicionales');
        });
    }
};
