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
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->char('initial_answer', 1)->nullable();
            $table->text('initial_reason')->nullable();
            $table->json('initial_markers')->nullable();
            $table->char('reviewed_answer', 1)->nullable();
            $table->text('review_reflection')->nullable();
            $table->json('reviewed_markers')->nullable();
            $table->boolean('is_correct_initial')->default(false);
            $table->boolean('is_correct_final')->default(false);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
