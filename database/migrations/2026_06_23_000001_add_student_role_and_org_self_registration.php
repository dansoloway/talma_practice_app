<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'teacher'");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_new', 20)->default('teacher');
            });
            DB::table('users')->update(['role_new' => DB::raw('role')]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_new', 'role');
            });
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_self_registration')->default(false)->after('access_mode');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_self_registration');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('users')->where('role', 'student')->update(['role' => 'teacher']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher'");
        } elseif ($driver === 'sqlite') {
            DB::table('users')->where('role', 'student')->update(['role' => 'teacher']);
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_old', 20)->default('teacher');
            });
            DB::table('users')->update(['role_old' => DB::raw('role')]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_old', 'role');
            });
        }
    }
};
