<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->foreignId('funcion_zootecnica_id')->nullable()->after('raza_id')->constrained('funcion_zootecnicas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropForeign(['funcion_zootecnica_id']);
            $table->dropColumn('funcion_zootecnica_id');
        });
    }
};
