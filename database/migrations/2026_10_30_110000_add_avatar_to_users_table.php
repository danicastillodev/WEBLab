<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // inicial | iniciales | foto
            $table->string('avatar_tipo', 20)->default('inicial')->after('email');
            $table->string('avatar_path')->nullable()->after('avatar_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_tipo', 'avatar_path']);
        });
    }
};
