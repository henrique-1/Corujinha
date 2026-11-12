<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tabelas que passam a expor UUIDv7 em rotas, formulários e payloads.
     *
     * @var list<string>
     */
    private array $uuidTables = [
        'users',
        'questions',
        'assessments',
        'classrooms',
        'student_attempts',
        'webauthn_credentials',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Adiciona coluna uuid (nullable inicialmente para backfill seguro)
        foreach ($this->uuidTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });

            DB::table($tableName)
                ->whereNull('uuid')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid7()]);
                    }
                });

            Schema::table($tableName, function (Blueprint $table) {
                $table->unique('uuid');
            });
        }

        // 2. Adiciona broadcast_key às salas de aula para canal WebSocket não enumerável do estudante
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('broadcast_key', 64)->nullable()->after('code');
        });

        DB::table('classrooms')
            ->whereNull('broadcast_key')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('classrooms')
                        ->where('id', $row->id)
                        ->update(['broadcast_key' => Str::random(40)]);
                }
            });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unique('broadcast_key');
        });

        // 3. Adiciona totp_last_used_slice para proteção anti-replay de códigos TOTP
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('totp_last_used_slice')->nullable()->after('totp_enabled_at');
        });

        // 4. Criptografa totp_secret existentes em repouso (caso estejam em texto plano Base32)
        DB::table('users')
            ->whereNotNull('totp_secret')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $raw = (string) $user->totp_secret;
                    if ($raw === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($raw);
                    } catch (Throwable) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['totp_secret' => Crypt::encryptString($raw)]);
                    }
                }
            });

        // 5. Remove duplicatas legadas de student_answers e adiciona índice único composto
        $duplicates = DB::table('student_answers')
            ->select('student_attempt_id', 'question_id', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('student_attempt_id', 'question_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('student_answers')
                ->where('student_attempt_id', $dup->student_attempt_id)
                ->where('question_id', $dup->question_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('student_answers', function (Blueprint $table) {
            $table->unique(['student_attempt_id', 'question_id'], 'student_answers_attempt_question_unique');
        });

        // 6. Move imagens de apoio existentes do disco público para o disco local privado
        DB::table('questions')
            ->whereNotNull('support_image_path')
            ->orderBy('id')
            ->chunkById(200, function ($questions) {
                foreach ($questions as $q) {
                    $path = $q->support_image_path;
                    if ($path && Storage::disk('public')->exists($path) && ! Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                        Storage::disk('public')->delete($path);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropUnique('student_answers_attempt_question_unique');
        });

        // Reverte criptografia de totp_secret para texto plano Base32
        DB::table('users')
            ->whereNotNull('totp_secret')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $val = (string) $user->totp_secret;
                    if ($val === '') {
                        continue;
                    }

                    try {
                        $decrypted = Crypt::decryptString($val);
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['totp_secret' => $decrypted]);
                    } catch (Throwable) {
                        // Já estava em texto plano
                    }
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('totp_last_used_slice');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['broadcast_key']);
            $table->dropColumn('broadcast_key');
        });

        foreach ($this->uuidTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
