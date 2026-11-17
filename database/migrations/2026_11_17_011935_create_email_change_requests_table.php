<?php

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
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('current_email');
            $table->string('new_email')->nullable();
            $table->string('step1_token', 64)->unique();
            $table->string('step2_token', 64)->nullable()->unique();
            $table->dateTime('step1_expires_at');
            $table->dateTime('step2_expires_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });

        // Habilita criptografia em repouso caso o banco seja MySQL / MariaDB (LGPD/TDE)
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                DB::statement("ALTER TABLE `email_change_requests` ENCRYPTION='Y'");
            } catch (Throwable) {
                try {
                    DB::statement('ALTER TABLE `email_change_requests` ENCRYPTED=YES');
                } catch (Throwable) {
                    // Silencioso se plugin de keyring não estiver configurado
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
