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
        Schema::create('grammar_concepts', function (Blueprint $table) {
            $table->id();
            $table->integer('section')->nullable();
            $table->string('grammar_topic');
            $table->string('grammar_sub_topic');
            $table->timestamps();
            
            // Index for efficient queries
            $table->index(['section', 'grammar_topic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grammar_concepts');
    }
};
