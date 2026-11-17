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
        Schema::table('questions', function (Blueprint $table) {
            $table->string('answer_type', 30)->default('multiple_choice')->after('area');
            $table->text('expected_answer')->nullable()->after('correct_option');
            $table->text('expected_answer_explanation')->nullable()->after('expected_answer');
            $table->char('correct_option', 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['answer_type', 'expected_answer', 'expected_answer_explanation']);
            $table->char('correct_option', 1)->nullable(false)->change();
        });
    }
};
