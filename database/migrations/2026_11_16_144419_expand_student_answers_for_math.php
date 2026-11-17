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
        Schema::table('student_answers', function (Blueprint $table) {
            $table->text('initial_answer')->nullable()->change();
            $table->text('reviewed_answer')->nullable()->change();
            $table->string('resolution_image_path', 500)->nullable()->after('reviewed_markers');
            $table->decimal('score', 5, 2)->nullable()->after('is_correct_final');
            $table->boolean('is_manually_graded')->default(false)->after('score');
            $table->decimal('manual_score', 5, 2)->nullable()->after('is_manually_graded');
            $table->text('teacher_comment')->nullable()->after('manual_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropColumn([
                'resolution_image_path',
                'score',
                'is_manually_graded',
                'manual_score',
                'teacher_comment',
            ]);
            $table->char('initial_answer', 1)->nullable()->change();
            $table->char('reviewed_answer', 1)->nullable()->change();
        });
    }
};
