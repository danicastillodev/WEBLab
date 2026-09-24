<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pruebas', function (Blueprint $table) {
            $table->dropUnique('pruebas_clave_unique');
            $table->dropColumn('clave');
        });
    }

    public function down(): void
    {
        Schema::table('pruebas', function (Blueprint $table) {
            $table->string('clave')->nullable()->unique();
        });
    }
};
