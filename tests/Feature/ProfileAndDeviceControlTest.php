<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\EmailChangeRequest;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use App\Notifications\ConfirmCurrentEmailChangeNotification;
use App\Notifications\ConfirmNewEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('professor pode visualizar e atualizar dados do perfil mantendo CPF imutavel', function () {
    $user = User::factory()->create([
        'name' => 'Professora Ana Clara',
        'cpf' => '12345678901',
        'email' => 'anaclara@escola.com',
        'role' => 'professor',
        'status' => 'active',
        'institution' => 'Escola Estadual Modelo',
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));
    $response->assertOk()
        ->assertSee('Professora Ana Clara')
        ->assertSee('Escola Estadual Modelo')
        ->assertSee('123.456.789-01');

    // Tenta atualizar incluindo tentativa de adulterar o CPF
    $updateResponse = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Professora Ana Clara Silva',
        'institution' => 'Instituto Federal de Ensino Tecnologico - Campus Central',
        'cpf' => '99999999999', // Deve ser ignorado
    ]);

    $updateResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success', 'Dados do perfil atualizados com sucesso!');

    $user->refresh();
    expect($user->name)->toBe('Professora Ana Clara Silva')
        ->and($user->institution)->toBe('Instituto Federal de Ensino Tecnologico - Campus Central')
        ->and($user->cpf)->toBe('12345678901'); // CPF permanece intocado
});

test('instituicao de atuacao deve aceitar ate 256 caracteres e falhar acima disso', function () {
    $user = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $valid256 = str_repeat('A', 256);
    $responseValid = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Professor Teste',
        'institution' => $valid256,
    ]);
    $responseValid->assertSessionHasNoErrors();
    expect($user->fresh()->institution)->toBe($valid256);

    $invalid257 = str_repeat('B', 257);
    $responseInvalid = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Professor Teste',
        'institution' => $invalid257,
    ]);
    $responseInvalid->assertSessionHasErrors(['institution']);
});

test('professor pode fazer upload e exclusao de foto de perfil', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $avatarFile = UploadedFile::fake()->image('foto_perfil.png', 400, 400);

    $uploadResponse = $this->actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => $avatarFile,
    ]);

    $uploadResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success', 'Foto de perfil atualizada com sucesso!');

    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);

    // Exclusão da foto de perfil
    $deleteResponse = $this->actingAs($user)->delete(route('profile.avatar.destroy'));
    $deleteResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success', 'Foto de perfil removida com sucesso.');

    $oldPath = $user->avatar_path;
    $user->refresh();
    expect($user->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

test('fluxo completo de alteracao de e-mail com Double-Opt-In', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'antigo@escola.com',
        'role' => 'professor',
        'status' => 'active',
    ]);

    // Etapa 1: Solicita alteração
    $requestResponse = $this->actingAs($user)->post(route('profile.email.request'));
    $requestResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ConfirmCurrentEmailChangeNotification::class);

    $emailRequest = EmailChangeRequest::where('user_id', $user->id)->latest()->first();
    expect($emailRequest)->not->toBeNull()
        ->and($emailRequest->step1_token)->toHaveLength(64)
        ->and($emailRequest->current_email)->toBe('antigo@escola.com');

    // Acessa o link de confirmação do e-mail atual
    $formResponse = $this->actingAs($user)->get(route('profile.email.change.form', ['token' => $emailRequest->step1_token]));
    $formResponse->assertOk()
        ->assertSee('Definir Novo E-mail');

    // Etapa 2: Submete novo e-mail
    $submitResponse = $this->actingAs($user)->post(route('profile.email.change.submit', ['token' => $emailRequest->step1_token]), [
        'new_email' => 'novo@escola.com',
        'new_email_confirmation' => 'novo@escola.com',
    ]);

    $submitResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    $emailRequest->refresh();
    expect($emailRequest->new_email)->toBe('novo@escola.com')
        ->and($emailRequest->step2_token)->toHaveLength(64);

    Notification::assertSentOnDemand(ConfirmNewEmailNotification::class);

    // Etapa 3: Clica no link enviado ao novo e-mail
    $confirmResponse = $this->actingAs($user)->get(route('profile.email.confirm', ['token' => $emailRequest->step2_token]));
    $confirmResponse->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success', 'Seu e-mail foi atualizado com sucesso para novo@escola.com!');

    $user->refresh();
    expect($user->email)->toBe('novo@escola.com');
    expect($emailRequest->fresh()->completed_at)->not->toBeNull();
});

test('dispositivo compartilhado entra com status waiting_authorization e professor pode autorizar', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Laboratorio',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 1',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Laboratório 1',
        'code' => 'LAB-001',
        'game_mode' => 'corujinha',
        'status' => 'open',
    ]);

    $sharedDeviceId = 'dev_escola_lab_pc_04';

    // Primeiro aluno entra no computador do laboratório
    $responseAluno1 = $this->post(route('estudante.iniciar'), [
        'code' => 'LAB-001',
        'student_name' => 'Lucas Silva',
        'device_identifier' => $sharedDeviceId,
    ]);
    $responseAluno1->assertRedirect(route('estudante.player', ['code' => 'LAB-001']));

    $attempt1 = StudentAttempt::where('classroom_id', $classroom->id)
        ->where('student_name', 'Lucas Silva')
        ->first();
    expect($attempt1->device_identifier)->toBe($sharedDeviceId)
        ->and($attempt1->is_device_authorized)->toBeTrue()
        ->and($attempt1->status)->toBe('in_progress');

    // Conclui a prova do primeiro aluno
    $attempt1->update(['status' => 'submitted', 'completed_at' => now()]);

    // O mesmo aluno tenta entrar novamente no mesmo computador -> Deve ser barrado!
    $responseMesmoAluno = $this->post(route('estudante.iniciar'), [
        'code' => 'LAB-001',
        'student_name' => 'Lucas Silva',
        'device_identifier' => $sharedDeviceId,
    ]);
    $responseMesmoAluno->assertRedirect()
        ->assertSessionHasErrors(['code']);

    // Outro aluno senta no mesmo computador compartilhado da escola
    $responseAluno2 = $this->post(route('estudante.iniciar'), [
        'code' => 'LAB-001',
        'student_name' => 'Mariana Souza',
        'device_identifier' => $sharedDeviceId,
    ]);

    // Deve ser redirecionado para a tela de aguardando autorização
    $responseAluno2->assertRedirect(route('estudante.aguardando_autorizacao', ['code' => 'LAB-001']));

    $attempt2 = StudentAttempt::where('classroom_id', $classroom->id)
        ->where('student_name', 'Mariana Souza')
        ->first();

    expect($attempt2->device_identifier)->toBe($sharedDeviceId)
        ->and($attempt2->status)->toBe('waiting_authorization')
        ->and($attempt2->is_device_authorized)->toBeFalse();

    // Mariana tenta acessar o player diretamente -> é redirecionada de volta para aguardando autorização
    $playerBlockResponse = $this->withSession([
        'current_attempt_id' => $attempt2->id,
        'current_attempt_uuid' => $attempt2->uuid,
    ])->get(route('estudante.player', ['code' => 'LAB-001']));

    $playerBlockResponse->assertRedirect(route('estudante.aguardando_autorizacao', ['code' => 'LAB-001']));

    // Professor autoriza o dispositivo no painel da sala
    $authResponse = $this->actingAs($professor)->from(route('professor.salas.show', $classroom))->post(route('professor.salas.tentativa.authorize_device', [
        'sala' => $classroom,
        'tentativa' => $attempt2,
    ]));

    $authResponse->assertRedirect(route('professor.salas.show', $classroom))
        ->assertSessionHas('success', 'Acesso do estudante Mariana Souza autorizado com sucesso!');

    $attempt2->refresh();
    expect($attempt2->status)->toBe('in_progress')
        ->and($attempt2->is_device_authorized)->toBeTrue();
});

test('no modo corujao questao esgotada por timeout eh salva com initial_answer nula e pontuacao zero', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado Corujão Noturno',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Quanto é 7 x 8?',
        'support_type' => 'text',
        'correct_option' => 'C',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Noturna 1',
        'code' => 'NOITE-01',
        'game_mode' => 'corujao',
        'status' => 'open',
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Pedro Alcantara',
        'access_code' => 'ALU-1234567890',
        'status' => 'in_progress',
        'started_at' => now(),
        'score' => 0,
        'is_device_authorized' => true,
    ]);

    // Submissão com tempo esgotado e resposta vazia/não selecionada
    $response = $this->withSession([
        'current_attempt_id' => $attempt->id,
        'current_attempt_uuid' => $attempt->uuid,
    ])->postJson(route('estudante.salvar_resposta', ['code' => 'NOITE-01']), [
        'question_id' => $question->id,
        'answer' => '',
        'reason' => '',
        'is_timeout' => true,
        'time_spent' => 180000,
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $savedAnswer = StudentAnswer::where('student_attempt_id', $attempt->id)
        ->where('question_id', $question->id)
        ->first();

    expect($savedAnswer)->not->toBeNull()
        ->and($savedAnswer->initial_answer)->toBeNull()
        ->and($savedAnswer->is_correct_initial)->toBeFalse()
        ->and($savedAnswer->score)->toBe(0.0);
});

test('perfil do usuario possui aba de Seguranca MFA e topbar nao exibe menu de seguranca', function () {
    $user = User::factory()->create([
        'name' => 'Professor Roberto',
        'role' => 'professor',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));
    $response->assertOk()
        ->assertSee('Dados Pessoais')
        ->assertSee('Segurança & MFA', false)
        ->assertSee('Autenticador TOTP')
        ->assertSee('Passkeys & Biometria', false)
        ->assertDontSee('Segurança (MFA)');

    $responseSecurityTab = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'seguranca']));
    $responseSecurityTab->assertOk()
        ->assertSee('Central de Segurança & MFA', false)
        ->assertSee('Autenticador TOTP');
});
