<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->unsignedInteger('animales_explotacion')->nullable()->after('cantidad');
            $table->unsignedInteger('animales_muertos')->nullable()->after('animales_explotacion');
            $table->unsignedInteger('animales_enfermos')->nullable()->after('animales_muertos');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropColumn(['animales_explotacion', 'animales_muertos', 'animales_enfermos']);
        });
    }
};
