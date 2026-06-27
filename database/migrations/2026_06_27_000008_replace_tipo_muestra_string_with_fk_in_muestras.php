<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muestras', function (Blueprint $table) {
            $table->dropColumn('tipo_muestra');
            $table->foreignId('tipo_muestra_id')->nullable()->after('cantidad')->constrained('tipo_muestras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('muestras', function (Blueprint $table) {
            $table->dropForeign(['tipo_muestra_id']);
            $table->dropColumn('tipo_muestra_id');
            $table->string('tipo_muestra')->nullable()->after('cantidad');
        });
    }
};
