<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
