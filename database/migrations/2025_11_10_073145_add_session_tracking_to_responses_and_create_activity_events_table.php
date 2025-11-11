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
            if (!Schema::hasColumn('responses', 'session_id')) {
                $table->uuid('session_id')->nullable()->after('user_id')->index();
            }
        });

        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable()->index();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type', 50);
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->string('status', 20);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['activity_type', 'activity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_events');

        Schema::table('responses', function (Blueprint $table) {
            if (Schema::hasColumn('responses', 'session_id')) {
                $table->dropColumn('session_id');
            }
        });
    }
};
