<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add is_org_wide and set all Default org courses to true for backward compatibility.
     */
    public function up(): void
    {
        Schema::table('organization_course', function (Blueprint $table) {
            $table->boolean('is_org_wide')->default(false)->after('course_id');
        });

        // All existing Default org courses must be org-wide (no regression)
        $defaultOrg = DB::table('organizations')->where('slug', 'default')->first();
        if ($defaultOrg) {
            DB::table('organization_course')
                ->where('organization_id', $defaultOrg->id)
                ->update(['is_org_wide' => true]);
        }

        Schema::table('organization_course', function (Blueprint $table) {
            $table->index(['organization_id', 'is_org_wide']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_course', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'is_org_wide']);
        });

        Schema::table('organization_course', function (Blueprint $table) {
            $table->dropColumn('is_org_wide');
        });
    }
};
