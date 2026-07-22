<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clear any partial create from a failed earlier attempt on MySQL.
        Schema::dropIfExists('learner_visit_lessons');
        Schema::dropIfExists('learner_visits');
        Schema::dropIfExists('learner_login_events');

        Schema::create('learner_login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_student_id')->nullable()->constrained('parent_students')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('learner_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_student_id')->nullable()->constrained('parent_students')->nullOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('last_seen_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('end_reason', 20)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'started_at']);
            $table->index(['organization_id', 'ended_at', 'last_seen_at']);
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('learner_visit_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_visit_id')->constrained('learner_visits')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->timestamps();

            $table->unique(['learner_visit_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_visit_lessons');
        Schema::dropIfExists('learner_visits');
        Schema::dropIfExists('learner_login_events');
    }
};
