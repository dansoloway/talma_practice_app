<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add organizations.is_root. System invariant: only one Root org exists.
     * MySQL does not support partial unique indexes; application-level guard in
     * Organization model and RootOrganizationSeeder enforces single Root.
     * SQLite (tests) gets a partial unique index for extra safety.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_root')->default(false)->after('access_mode')->index();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX organizations_is_root_unique ON organizations (is_root) WHERE is_root = 1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS organizations_is_root_unique');
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('is_root');
        });
    }
};
