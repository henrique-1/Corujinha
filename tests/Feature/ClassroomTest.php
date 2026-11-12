<?php

use App\Events\ClassroomClosed;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('professor cria sala de aula com código único e toggle de revisão coletiva', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado Língua Portuguesa 7º Ano',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Comando da pergunta teste',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    $assessment->questions()->attach($question->id, ['order' => 1]);

    $response = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Turma 7º Ano A',
        'assessment_id' => $assessment->id,
        'code' => 'SESI-7A99',
        'collective_review_enabled' => '1',
    ]);

    $classroom = Classroom::where('code', 'SESI-7A99')->first();

    expect($classroom)->not->toBeNull()
        ->and($classroom->collective_review_enabled)->toBeTrue()
        ->and($classroom->is_active)->toBeTrue();

    $response->assertRedirect(route('professor.salas.show', $classroom));
});

test('professor pode criar sala com revisão coletiva desativada', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado Rápido',
    ]);

    $response = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Turma 7º Ano B',
        'assessment_id' => $assessment->id,
        'code' => 'SESI-7B00',
        'collective_review_enabled' => '0',
    ]);

    $classroom = Classroom::where('code', 'SESI-7B00')->first();

    expect($classroom)->not->toBeNull()
        ->and($classroom->collective_review_enabled)->toBeFalse();
});

test('professor visualiza os detalhes do histórico de respostas do estudante', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Teste',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Qual é a alternativa correta?',
        'support_type' => 'text',
        'correct_option' => 'C',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Especial',
        'code' => 'TEST-0001',
        'collective_review_enabled' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Exemplo',
        'started_at' => now(),
        'status' => 'reviewed',
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'initial_answer' => 'A',
        'initial_reason' => 'Pensei que fosse A inicialmente',
        'reviewed_answer' => 'C',
        'review_reflection' => 'Mudei para C após a correção coletiva',
        'is_correct_initial' => false,
        'is_correct_final' => true,
    ]);

    $attempt->recalculateScore();

    expect($attempt->score)->toBe(1);

    $response = $this->actingAs($professor)->get(route('professor.salas.tentativa', [$classroom, $attempt]));

    $response->assertOk()
        ->assertSee('Aluno Exemplo')
        ->assertSee('Pensei que fosse A inicialmente')
        ->assertSee('Mudei para C após a correção coletiva')
        ->assertSee('Alterou de A para C');
});

test('nao permite criar sala com codigo de acesso duplicado via requisicao', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Teste',
    ]);

    Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Original',
        'code' => 'CODIGO-DUPLO',
        'collective_review_enabled' => true,
    ]);

    $response = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Sala Duplicada',
        'assessment_id' => $assessment->id,
        'code' => 'CODIGO-DUPLO',
        'collective_review_enabled' => '1',
    ]);

    $response->assertSessionHasErrors(['code']);
    expect(Classroom::where('code', 'CODIGO-DUPLO')->count())->toBe(1);
});

test('banco de dados lanca violacao de integridade caso ocorra tentativa direta de codigo repetido', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Teste',
    ]);

    Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala 1',
        'code' => 'UNIQUE-1234',
    ]);

    expect(fn () => Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala 2',
        'code' => 'UNIQUE-1234',
    ]))->toThrow(QueryException::class);
});

test('metodo generateUniqueCode gera codigo exclusivo e evita colisoes com codigos existentes', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Teste',
    ]);

    $existingCode = Classroom::generateUniqueCode('AVAL');

    Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala com Codigo Gerado',
        'code' => $existingCode,
    ]);

    $newCode = Classroom::generateUniqueCode('AVAL');

    expect($newCode)->not->toBe($existingCode)
        ->and(Classroom::where('code', $newCode)->exists())->toBeFalse();
});

test('professor define e edita limite de alteracoes na revisao coletiva dentro do intervalo 1 a N', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Simulado 2 Questões']);

    $q1 = Question::create(['user_id' => $professor->id, 'prompt' => 'Questão 1', 'support_type' => 'none', 'correct_option' => 'A']);
    $q2 = Question::create(['user_id' => $professor->id, 'prompt' => 'Questão 2', 'support_type' => 'none', 'correct_option' => 'B']);
    $assessment->questions()->attach([$q1->id => ['order' => 1], $q2->id => ['order' => 2]]);

    // Rejeita 0 ou maior que N (3 > 2)
    $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Sala Inválida 0',
        'assessment_id' => $assessment->id,
        'code' => 'LIM-INV0',
        'collective_review_enabled' => '1',
        'max_review_changes' => 0,
    ])->assertSessionHasErrors(['max_review_changes']);

    $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Sala Inválida 3',
        'assessment_id' => $assessment->id,
        'code' => 'LIM-INV3',
        'collective_review_enabled' => '1',
        'max_review_changes' => 3,
    ])->assertSessionHasErrors(['max_review_changes']);

    // Criação válida com limite 1
    $storeResponse = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Sala Limite 1',
        'assessment_id' => $assessment->id,
        'code' => 'LIM-OK1',
        'collective_review_enabled' => '1',
        'max_review_changes' => 1,
    ]);

    $classroom = Classroom::where('name', 'Sala Limite 1')->firstOrFail();
    $storeResponse->assertRedirect(route('professor.salas.show', $classroom));
    expect($classroom->collective_review_enabled)->toBeTrue()
        ->and($classroom->max_review_changes)->toBe(1);

    // Edição altera nome, limite para 2 e depois desativa revisão coletiva
    $this->actingAs($professor)->get(route('professor.salas.edit', $classroom))->assertOk();

    $this->actingAs($professor)->put(route('professor.salas.update', $classroom), [
        'name' => 'Sala Editada 2',
        'collective_review_enabled' => '1',
        'max_review_changes' => 2,
    ])->assertRedirect(route('professor.salas.show', $classroom));

    $classroom->refresh();
    expect($classroom->name)->toBe('Sala Editada 2')
        ->and($classroom->max_review_changes)->toBe(2);

    $this->actingAs($professor)->put(route('professor.salas.update', $classroom), [
        'name' => 'Sala Sem Revisão',
        'collective_review_enabled' => '0',
    ])->assertRedirect(route('professor.salas.show', $classroom));

    $classroom->refresh();
    expect($classroom->collective_review_enabled)->toBeFalse()
        ->and($classroom->max_review_changes)->toBeNull();
});

test('fechamento da sala e irreversivel e entrar na devolutiva encerra a sala exibindo destaques comentarios e listas nominais', function () {
    Event::fake([ClassroomClosed::class]);

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Avaliação com Devolutiva']);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Identifique o efeito de sentido principal',
        'support_type' => 'text',
        'support_text' => 'O trecho central revela humor sutil.',
        'expected_commands' => 'efeito de sentido',
        'command_feedback' => 'O comando pede o efeito expressivo.',
        'expected_clues' => 'humor sutil',
        'clue_feedback' => 'A pista está em humor sutil.',
        'correct_option' => 'A',
    ]);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'A', 'text' => 'Ironia leve', 'feedback' => 'Correta pela pista do texto.']);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'B', 'text' => 'Drama intenso', 'feedback' => 'Distrator oposto ao tom.']);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Devolutiva',
        'code' => 'DEV-01',
        'is_active' => true,
        'collective_review_enabled' => true,
        'max_review_changes' => 1,
    ]);

    $acertoAttempt = StudentAttempt::create(['classroom_id' => $classroom->id, 'student_name' => 'Mariana Acerto', 'started_at' => now()]);
    StudentAnswer::create(['student_attempt_id' => $acertoAttempt->id, 'question_id' => $question->id, 'initial_answer' => 'A', 'reviewed_answer' => 'A', 'is_correct_initial' => true, 'is_correct_final' => true]);

    $erroAttempt = StudentAttempt::create(['classroom_id' => $classroom->id, 'student_name' => 'Lucas Erro', 'started_at' => now()]);
    StudentAnswer::create(['student_attempt_id' => $erroAttempt->id, 'question_id' => $question->id, 'initial_answer' => 'B', 'reviewed_answer' => 'B', 'is_correct_initial' => false, 'is_correct_final' => false]);

    // Entrar na Devolutiva fecha a sala e dispara evento Reverb
    $this->actingAs($professor)
        ->post(route('professor.salas.devolutiva.enter', $classroom))
        ->assertRedirect(route('professor.salas.devolutiva', $classroom));

    $classroom->refresh();
    expect($classroom->is_active)->toBeFalse();
    Event::assertDispatched(ClassroomClosed::class, fn ($e) => $e->classroom->id === $classroom->id);

    // Tentativa de reabrir via toggle_status falha e mantém sala encerrada
    $this->actingAs($professor)
        ->post(route('professor.salas.toggle_status', $classroom))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($classroom->fresh()->is_active)->toBeFalse();

    // Página da Devolutiva exibe marcações, devolutivas, contagem de acertos e listas nominais
    $this->actingAs($professor)
        ->get(route('professor.salas.devolutiva', $classroom))
        ->assertOk()
        ->assertSee('O comando pede o efeito expressivo.')
        ->assertSee('A pista está em humor sutil.')
        ->assertSee('Correta pela pista do texto.')
        ->assertSee('1 acerto(s)')
        ->assertSee('1 erro(s)')
        ->assertSee('Mariana Acerto')
        ->assertSee('Lucas Erro');
});
