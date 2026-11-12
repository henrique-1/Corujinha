<?php

use App\Models\StudentAttempt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_attempts', function (Blueprint $table) {
            $table->string('access_code', 20)->nullable()->after('uuid');
        });

        DB::table('student_attempts')
            ->whereNull('access_code')
            ->orderBy('id')
            ->each(function (object $attempt): void {
                DB::table('student_attempts')
                    ->where('id', $attempt->id)
                    ->update(['access_code' => StudentAttempt::generateUniqueAccessCode()]);
            });

        Schema::table('student_attempts', function (Blueprint $table) {
            $table->unique('access_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attempts', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn('access_code');
        });
    }
};
