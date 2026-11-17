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
        Schema::table('student_attempts', function (Blueprint $table) {
            $table->string('device_identifier', 128)->nullable()->after('access_code');
            $table->string('device_ip', 45)->nullable()->after('device_identifier');
            $table->boolean('is_device_authorized')->default(true)->after('device_ip');

            $table->index(['classroom_id', 'device_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attempts', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'device_identifier']);
            $table->dropColumn(['device_identifier', 'device_ip', 'is_device_authorized']);
        });
    }
};
