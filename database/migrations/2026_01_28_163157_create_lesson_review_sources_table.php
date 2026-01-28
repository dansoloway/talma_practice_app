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
        Schema::create('lesson_review_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->foreignId('source_lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            // Ensure a lesson can't review itself
            $table->unique(['review_lesson_id', 'source_lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_review_sources');
    }
};
