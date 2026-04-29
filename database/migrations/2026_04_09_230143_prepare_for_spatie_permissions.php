<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Save existing roles to a temp table
        Schema::create('_temp_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
        });

        Schema::create('_temp_user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->string('role_name');
        });

        // Copy existing data
        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            DB::table('_temp_roles')->insert([
                'id'          => $role->id,
                'name'        => $role->name,
                'description' => $role->description,
            ]);
        }

        $users = DB::table('users')->whereNotNull('role_id')->get();
        foreach ($users as $user) {
            $role = DB::table('roles')->find($user->role_id);
            if ($role) {
                DB::table('_temp_user_roles')->insert([
                    'user_id'   => $user->id,
                    'role_name' => $role->name,
                ]);
            }
        }

        // Drop old structure
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::dropIfExists('_temp_user_roles');
        Schema::dropIfExists('_temp_roles');
    }
};
