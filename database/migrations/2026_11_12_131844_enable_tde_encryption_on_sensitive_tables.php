<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas contendo dados pessoais ou sensíveis sob os padrões da LGPD.
     *
     * @var array<int, string>
     */
    private array $sensitiveTables = [
        'users',
        'student_attempts',
        'student_answers',
        'webauthn_credentials',
        'sessions',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Aplica apenas em MySQL ou MariaDB (ignora SQLite nos testes de CI/CD)
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
            } catch (Throwable $e) {
                try {
                    // Fallback para sintaxe nativa do MariaDB
                    DB::statement("ALTER TABLE `{$table}` ENCRYPTED=YES");
                } catch (Throwable) {
                    // Se o keyring não estiver carregado no banco local/dev, ignora sem quebrar a migration
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
