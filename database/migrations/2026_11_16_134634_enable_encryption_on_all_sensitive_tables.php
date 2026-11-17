<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas contendo dados pessoais, credenciais ou conteúdos pedagógicos sensíveis sob LGPD/TDE.
     *
     * @var array<int, string>
     */
    private array $sensitiveTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'webauthn_credentials',
        'student_attempts',
        'student_answers',
        'classrooms',
        'questions',
        'question_options',
        'assessments',
        'question_folders',
        'question_folder_shares',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Aplica apenas em MySQL ou MariaDB (ignora SQLite nos testes locais/CI)
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->sensitiveTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            try {
                // Sintaxe padrão do MySQL 8.x / Percona Server (Keyring ativo)
                DB::statement("ALTER TABLE `{$table}` ENCRYPTION='Y'");
            } catch (Throwable) {
                try {
                    // Sintaxe nativa do MariaDB (TDE / InnoDB Encryption)
                    DB::statement("ALTER TABLE `{$table}` ENCRYPTED=YES");
                } catch (Throwable) {
                    // Silencioso se o plugin de chaveamento não estiver ativo no ambiente local
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->sensitiveTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            try {
                DB::statement("ALTER TABLE `{$table}` ENCRYPTION='N'");
            } catch (Throwable) {
                try {
                    DB::statement("ALTER TABLE `{$table}` ENCRYPTED=NO");
                } catch (Throwable) {
                    // Silencioso
                }
            }
        }
    }
};
