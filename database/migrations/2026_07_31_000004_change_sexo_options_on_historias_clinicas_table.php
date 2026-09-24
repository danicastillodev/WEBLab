<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->enum('sexo', ['Hembra', 'Macho', 'Ambos', 'NA'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->enum('sexo', ['Macho', 'Hembra', 'Castrado', 'NR'])->nullable()->change();
        });
    }
};
