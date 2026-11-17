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
        Schema::create('question_folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#623bae');
            $table->string('visibility', 20)->default('private');
            $table->timestamps();
        });

        Schema::create('question_folder_shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('question_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('permission', 20)->default('view');
            $table->string('invitation_token', 64)->nullable();
            $table->timestamps();

            $table->unique(['question_folder_id', 'email']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('question_folders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn('folder_id');
        });

        Schema::dropIfExists('question_folder_shares');
        Schema::dropIfExists('question_folders');
    }
};
