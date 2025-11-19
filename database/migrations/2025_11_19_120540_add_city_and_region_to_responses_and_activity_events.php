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
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->index(['country', 'city']);
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('country');
            $table->string('region', 100)->nullable()->after('city');
            $table->index(['country', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropIndex(['country', 'city']);
            $table->dropColumn(['city', 'region']);
        });

        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['country', 'city']);
            $table->dropColumn(['city', 'region']);
        });
    }
};
