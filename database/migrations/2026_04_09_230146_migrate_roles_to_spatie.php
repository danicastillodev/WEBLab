<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add description to Spatie's roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });

        // Restore roles into Spatie's table
        $tempRoles = DB::table('_temp_roles')->get();
        foreach ($tempRoles as $role) {
            DB::table('roles')->insert([
                'name'        => $role->name,
                'description' => $role->description,
                'guard_name'  => 'web',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Re-assign users to roles via Spatie's model_has_roles
        $tempUserRoles = DB::table('_temp_user_roles')->get();
        foreach ($tempUserRoles as $ur) {
            $spatieRole = DB::table('roles')->where('name', $ur->role_name)->first();
            if ($spatieRole) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $spatieRole->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id'   => $ur->user_id,
                ]);
            }
        }

        // Clean up temp tables
        Schema::dropIfExists('_temp_user_roles');
        Schema::dropIfExists('_temp_roles');
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
