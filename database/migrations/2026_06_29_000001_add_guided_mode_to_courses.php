<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('guided_mode_enabled')->default(false)->after('is_active');
            $table->json('guided_flow')->nullable()->after('guided_mode_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['guided_mode_enabled', 'guided_flow']);
        });
    }
};
