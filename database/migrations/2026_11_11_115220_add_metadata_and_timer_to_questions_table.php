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
            $table->string('area')->nullable()->after('focus');
            $table->string('grade_level')->nullable()->after('area');
            $table->unsignedInteger('time_limit_seconds')->default(180)->after('grade_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['area', 'grade_level', 'time_limit_seconds']);
        });
    }
};
