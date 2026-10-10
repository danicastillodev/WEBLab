<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->unsignedBigInteger('numero')->nullable()->unique()->after('id');
        });

        // Los servicios existentes también necesitan su consecutivo: se numeran
        // en el orden en que fueron dados de alta.
        DB::table('servicios')->orderBy('id')->pluck('id')->each(function ($id, $i) {
            DB::table('servicios')->where('id', $id)->update(['numero' => $i + 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('numero');
        });
    }
};
