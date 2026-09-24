<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('razas', function (Blueprint $table) {
            $table->foreignId('especie_id')->nullable()->after('nombre')->constrained('especies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('razas', function (Blueprint $table) {
            $table->dropForeign(['especie_id']);
            $table->dropColumn('especie_id');
        });
    }
};
