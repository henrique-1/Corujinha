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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 14)->nullable()->unique()->after('email');
            $table->string('role', 30)->default('professor')->after('cpf');
            $table->string('status', 30)->default('pending')->after('role');
            $table->string('invitation_token', 80)->nullable()->unique()->after('status');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
            $table->text('totp_secret')->nullable()->after('remember_token');
            $table->timestamp('totp_enabled_at')->nullable()->after('totp_secret');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->dropUnique(['invitation_token']);
            $table->dropColumn([
                'cpf',
                'role',
                'status',
                'invitation_token',
                'invitation_sent_at',
                'totp_secret',
                'totp_enabled_at',
            ]);
            $table->string('password')->nullable(false)->change();
        });
    }
};
