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
            $table->string('ip_address', 45)->nullable()->after('device_type');
            $table->string('country', 2)->nullable()->after('ip_address');
            $table->index('ip_address');
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('device_type');
            $table->string('country', 2)->nullable()->after('ip_address');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'country']);
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'country']);
        });
    }
};
