<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\PasskeyService;
use App\Services\TextHighlightService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('FINDING-0001: showResult redireciona tentativa in_progress para o player e tentativa submitted para revisao quando sala ativa', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Prova Blindada']);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Pergunta secreta com gabarito',
        'support_type' => 'text',
        'correct_option' => 'D',
        'command_feedback' => 'Segredo do gabarito D',
    ]);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'D', 'text' => 'Gabarito D', 'feedback' => 'Explicação secreta']);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Segurança 1',
        'code' => 'AVAL-SEC001',
        'collective_review_enabled' => true,
        'is_active' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Curioso',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    // Tentativa em andamento não pode ver /resultado
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.resultado', $classroom->code))
        ->assertRedirect(route('estudante.player', $classroom->code));

    // Tentativa submetida mas sem concluir revisão (quando revisão está ativa) é redirecionada para /revisao
    $attempt->update(['status' => 'submitted', 'completed_at' => now()]);
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.resultado', $classroom->code))
        ->assertRedirect(route('estudante.revisao', $classroom->code));

    // Somente após reviewed (ou sala fechada) pode acessar /resultado
    $attempt->update(['status' => 'reviewed', 'reviewed_at' => now()]);
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.resultado', $classroom->code))
        ->assertOk()
        ->assertSee('Explicação secreta');
});

test('FINDING-0002: saveAnswer bloqueia questao fora da avaliacao e bloqueia reenvio apos submissao da tentativa', function () {
    $prof1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $prof2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $qAllowed = Question::create([
        'user_id' => $prof1->id,
        'prompt' => 'Questão da Avaliação',
        'support_type' => 'text',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'correct_option' => 'A',
    ]);
    $qForeign = Question::create([
        'user_id' => $prof2->id,
        'prompt' => 'Questão de Outro Professor',
        'support_type' => 'text',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'correct_option' => 'C',
    ]);

    $assessment = Assessment::create(['user_id' => $prof1->id, 'title' => 'Prova Escopo']);
    $assessment->questions()->attach($qAllowed->id, ['order' => 1, 'points' => 2.0]);

    $classroom = Classroom::create([
        'user_id' => $prof1->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Escopo',
        'code' => 'AVAL-SEC002',
        'is_active' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Teste',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    // Tenta responder questão fora da avaliação -> 422 e score permanece 0
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $classroom->code), [
            'question_id' => $qForeign->uuid,
            'answer' => 'C',
            'reason' => 'Tentando inflar pontuação',
            'markers' => [],
        ])
        ->assertStatus(422);

    expect($attempt->fresh()->score)->toBe(0);

    // Responde questão válida usando UUID
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $classroom->code), [
            'question_id' => $qAllowed->uuid,
            'answer' => 'B',
            'reason' => 'Chutei B',
            'markers' => [],
        ])
        ->assertOk();

    // Submete tentativa
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_atividade', $classroom->code));

    expect($attempt->fresh()->status)->toBe('submitted');

    // Tenta alterar resposta inicial após submissão -> 422 bloqueado
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $classroom->code), [
            'question_id' => $qAllowed->uuid,
            'answer' => 'A',
            'reason' => 'Tentando corrigir após ver resultado',
            'markers' => [],
        ])
        ->assertStatus(422);

    $saved = StudentAnswer::where('student_attempt_id', $attempt->id)->first();
    expect($saved->initial_answer)->toBe('B');
});

test('FINDING-0003: finishReview bloqueia sala com revisao desativada e impede replay sequencial apos reviewed', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $q1 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Q1',
        'support_type' => 'text',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'correct_option' => 'B',
    ]);
    $q2 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Q2',
        'support_type' => 'text',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'correct_option' => 'B',
    ]);

    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Prova Revisão']);
    $assessment->questions()->attach($q1->id, ['order' => 1]);
    $assessment->questions()->attach($q2->id, ['order' => 2]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Revisão Única',
        'code' => 'AVAL-SEC003',
        'collective_review_enabled' => true,
        'max_review_changes' => 1,
        'is_active' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Replay',
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'submitted',
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $q1->id,
        'initial_answer' => 'A',
        'is_correct_initial' => false,
        'is_correct_final' => false,
    ]);
    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $q2->id,
        'initial_answer' => 'A',
        'is_correct_initial' => false,
        'is_correct_final' => false,
    ]);

    // 1ª submissão da revisão altera Q1 (dentro do limite max_review_changes = 1)
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $classroom->code), [
            'reviews' => [
                $q1->uuid => ['reviewed_answer' => 'B', 'review_reflection' => 'Corrigi Q1'],
                $q2->uuid => ['reviewed_answer' => 'A', 'review_reflection' => 'Mantive Q2'],
            ],
        ])
        ->assertRedirect(route('estudante.resultado', $classroom->code));

    expect($attempt->fresh()->status)->toBe('reviewed')
        ->and($attempt->fresh()->score)->toBe(1);

    // 2ª submissão (replay sequencial tentando corrigir Q2 também) deve ser ignorada
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $classroom->code), [
            'reviews' => [
                $q2->uuid => ['reviewed_answer' => 'B', 'review_reflection' => 'Tentando burlar limite'],
            ],
        ])
        ->assertRedirect(route('estudante.resultado', $classroom->code));

    expect($attempt->fresh()->score)->toBe(1);

    // submitAttempt não pode regredir status reviewed para submitted
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_atividade', $classroom->code));

    expect($attempt->fresh()->status)->toBe('reviewed');
});

test('FINDING-0005: canais privados de broadcast autorizam apenas proprietario ou super_admin', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
    ]);
    Broadcast::purge('reverb');
    require base_path('routes/channels.php');

    $owner = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $intruder = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

    $assessment = Assessment::create(['user_id' => $owner->id, 'title' => 'Avaliação']);
    $classroom = Classroom::create([
        'user_id' => $owner->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Privada',
        'code' => 'AVAL-SEC005',
        'is_active' => true,
    ]);

    // Intruso não pode autenticar no canal do professor proprietário nem da sala dele
    $this->actingAs($intruder)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-professor.'.$owner->uuid,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-classroom.monitor.'.$classroom->uuid,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin.professores',
        ])
        ->assertForbidden();

    // Proprietário autentica no próprio canal e na sala dele
    $this->actingAs($owner)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-classroom.monitor.'.$classroom->uuid,
        ])
        ->assertOk();

    // Super admin autentica em admin.professores
    $this->actingAs($admin)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin.professores',
        ])
        ->assertOk();
});

test('FINDING-0006: Passkey exige flag User Verified (UV) e rejeita sign_count regressivo', function () {
    $service = new PasskeyService;
    $privateKey = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);
    $details = openssl_pkey_get_details($privateKey);

    $professor = User::factory()->create([
        'email' => 'uv.test@corujinha.edu',
        'role' => 'professor',
        'status' => 'active',
        'totp_secret' => 'SECRETBASE32ABC',
        'totp_enabled_at' => now(),
    ]);

    $credentialId = $service->base64UrlEncode(random_bytes(16));
    WebauthnCredential::create([
        'user_id' => $professor->id,
        'credential_id' => $credentialId,
        'public_key' => $details['key'],
        'attestation_type' => 'spki:-7',
        'sign_count' => 5,
        'user_handle' => (string) $professor->id,
        'name' => 'Chave UV',
    ]);

    // 1. Tentativa sem flag UV (0x01 apenas UP) -> deve rejeitar com 422
    $opt1 = $this->postJson(route('login.passkey.options'), ['email' => $professor->email]);
    $clientData1 = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $opt1->json('challenge'),
        'origin' => 'http://localhost:8000',
    ]);
    $authDataNoUv = hash('sha256', $opt1->json('rpId'), true).chr(0x01).pack('N', 6);
    openssl_sign($authDataNoUv.hash('sha256', $clientData1, true), $sig1, $privateKey, OPENSSL_ALGO_SHA256);

    $this->postJson(route('login.passkey.verify'), [
        'id' => $credentialId,
        'clientDataJSON' => $service->base64UrlEncode($clientData1),
        'authenticatorData' => $service->base64UrlEncode($authDataNoUv),
        'signature' => $service->base64UrlEncode($sig1),
    ])->assertStatus(422);

    // 2. Tentativa com UV (0x05), mas signCount regressivo (5 <= 5) -> deve rejeitar com 422
    $opt2 = $this->postJson(route('login.passkey.options'), ['email' => $professor->email]);
    $clientData2 = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $opt2->json('challenge'),
        'origin' => 'http://localhost:8000',
    ]);
    $authDataReplayCount = hash('sha256', $opt2->json('rpId'), true).chr(0x05).pack('N', 5);
    openssl_sign($authDataReplayCount.hash('sha256', $clientData2, true), $sig2, $privateKey, OPENSSL_ALGO_SHA256);

    $this->postJson(route('login.passkey.verify'), [
        'id' => $credentialId,
        'clientDataJSON' => $service->base64UrlEncode($clientData2),
        'authenticatorData' => $service->base64UrlEncode($authDataReplayCount),
        'signature' => $service->base64UrlEncode($sig2),
    ])->assertStatus(422);
});

test('FINDING-0007 e 0016: TOTP bloqueia replay do mesmo codigo na mesma janela e exige step-up para desativar MFA', function () {
    $totpService = new TotpService;
    $secret = $totpService->generateSecret();
    $currentSlice = (int) floor(time() / 30);
    $validCode = $totpService->calculateCode($secret, $currentSlice);

    $professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'password' => Hash::make('SenhaForteSegura12345!'),
        'totp_secret' => $secret,
        'totp_enabled_at' => now(),
    ]);

    // Primeiro uso do código TOTP para este slice deve ser aceito
    expect($totpService->verifyOnce($professor, $validCode))->toBeTrue();

    // Segundo uso (replay) do mesmo código na mesma janela temporal deve ser rejeitado
    expect($totpService->verifyOnce($professor, $validCode))->toBeFalse();

    // Tentativa de desativar TOTP sem step-up (password.confirm) redireciona para /seguranca/confirmar
    $this->actingAs($professor)
        ->post(route('security.totp.disable'))
        ->assertRedirect(route('password.confirm'));

    expect($professor->fresh()->isTotpEnabled())->toBeTrue();

    // QR Code é gerado localmente em SVG inline sem chamar api.qrserver.com
    $userWithoutTotp = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($userWithoutTotp)
        ->get(route('security.index'))
        ->assertOk()
        ->assertDontSee('api.qrserver.com', false)
        ->assertSee('<svg', false);
});

test('FINDING-0008 e 0018: imagem de apoio fica protegida contra acesso anonimo e codigo de sala possui 6 caracteres com rate limit', function () {
    Storage::fake('local');
    $prof1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $prof2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $imagePath = UploadedFile::fake()->image('privada.png')->store('questions/images', 'local');
    $question = Question::create([
        'user_id' => $prof1->id,
        'support_type' => 'image',
        'support_image_path' => $imagePath,
        'prompt' => 'Questão com imagem',
        'correct_option' => 'A',
    ]);

    // Visitante anônimo sem sessão de tentativa não pode acessar a imagem -> 404
    $this->get(route('midia.questao', $question))->assertNotFound();

    // Outro professor não proprietário não pode acessar a imagem -> 404
    $this->actingAs($prof2)->get(route('midia.questao', $question))->assertNotFound();

    // Professor proprietário acessa normalmente -> 200
    $this->actingAs($prof1)->get(route('midia.questao', $question))->assertOk();

    // Código de sala gerado possui formato AVAL-XXXXXX (6 caracteres alfanuméricos)
    $generatedCode = Classroom::generateUniqueCode();
    expect($generatedCode)->toMatch('/^AVAL-[A-Z0-9]{6}$/');

    // Rate limit bloqueia enumeração de códigos inexistentes após 20 tentativas
    RateLimiter::clear('sala-code-miss:127.0.0.1');
    for ($i = 0; $i < 20; $i++) {
        $this->post(route('estudante.iniciar'), [
            'code' => 'AVAL-NAOEX'.$i,
            'student_name' => 'Teste Rate Limit',
        ]);
    }

    $this->post(route('estudante.iniciar'), [
        'code' => 'AVAL-NAOEX99',
        'student_name' => 'Teste Rate Limit',
    ])->assertStatus(429);
});

test('FINDING-0010 e 0017: TextHighlightService ignora markers malformados sem TypeError e headers de seguranca sao enviados', function () {
    $html = '<p>Texto de teste para o marca-texto seguro.</p>';
    $malformedMarkers = [
        'string-invalida',
        ['kind' => 'command', 'target' => 'support', 'text' => ['array-invalido']],
        ['kind' => 'evidence', 'target' => 'support', 'text' => 'marca-texto seguro'],
    ];

    $result = TextHighlightService::applyHighlights($html, $malformedMarkers, 'support');
    expect($result)->toContain('<mark')
        ->and($result)->toContain('marca-texto seguro</mark>');

    // Verifica headers de segurança HTTP e uso de UUID nas rotas públicas/autenticadas
    $response = $this->get(route('login'));
    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('UUIDs sao utilizados em todas as rotas de modelos expostos ao usuario', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Pergunta UUID',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);
    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Avaliação UUID']);
    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala UUID',
        'code' => 'AVAL-UUID01',
        'is_active' => true,
    ]);
    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Estudante UUID',
        'started_at' => now(),
    ]);

    expect($professor->getRouteKey())->toBe($professor->uuid)
        ->and($question->getRouteKey())->toBe($question->uuid)
        ->and($assessment->getRouteKey())->toBe($assessment->uuid)
        ->and($classroom->getRouteKey())->toBe($classroom->uuid)
        ->and($attempt->getRouteKey())->toBe($attempt->uuid)
        ->and(route('professor.perguntas.edit', $question))->toContain($question->uuid)
        ->and(route('professor.avaliacoes.edit', $assessment))->toContain($assessment->uuid)
        ->and(route('professor.salas.show', $classroom))->toContain($classroom->uuid)
        ->and(route('professor.salas.tentativa', [$classroom, $attempt]))->toContain($classroom->uuid)->toContain($attempt->uuid);
});
