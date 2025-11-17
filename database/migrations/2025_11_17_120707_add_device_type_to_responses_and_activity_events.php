<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('session_id')->index();
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->string('device_type', 20)->nullable()->after('session_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropColumn('device_type');
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropColumn('device_type');
        });
    }
};
