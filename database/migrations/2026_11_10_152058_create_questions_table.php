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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('focus')->nullable();
            $table->string('support_type', 20)->default('text'); // 'text' ou 'image'
            $table->string('support_title')->nullable();
            $table->longText('support_text')->nullable();
            $table->string('support_image_path')->nullable();
            $table->text('prompt');
            $table->boolean('require_command_highlight')->default(true);
            $table->boolean('require_clue_highlight')->default(true);
            $table->text('expected_commands')->nullable();
            $table->text('command_feedback')->nullable();
            $table->text('expected_clues')->nullable();
            $table->text('clue_feedback')->nullable();
            $table->char('correct_option', 1); // 'A', 'B', 'C', 'D' ou 'E'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
