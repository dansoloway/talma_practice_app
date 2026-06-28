<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('native_language', 20)->nullable()->after('gender');
        });

        Schema::table('parent_students', function (Blueprint $table) {
            $table->string('native_language', 20)->nullable()->after('gender');
        });

        Schema::table('voice_samples', function (Blueprint $table) {
            $table->string('native_language', 20)->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('voice_samples', function (Blueprint $table) {
            $table->dropColumn('native_language');
        });

        Schema::table('parent_students', function (Blueprint $table) {
            $table->dropColumn('native_language');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('native_language');
        });
    }
};
