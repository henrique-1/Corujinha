<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('resume-miss:127.0.0.1');

    $this->professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);

    $this->assessment = Assessment::create([
        'user_id' => $this->professor->id,
        'title' => 'Avaliação de Retomada',
    ]);

    $this->question = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Questão teste',
        'support_type' => 'text',
        'support_text' => 'Texto base da questão.',
        'correct_option' => 'A',
    ]);

    QuestionOption::create([
        'question_id' => $this->question->id,
        'letter' => 'A',
        'text' => 'Alternativa A',
    ]);

    $this->assessment->questions()->attach($this->question->id, ['order' => 1]);

    $this->classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Turma 9º A',
        'code' => Classroom::generateUniqueCode(),
        'is_active' => true,
    ]);
});

test('tentativa de aluno gera access_code único no padrão ALU-10caracteres com alfabeto alfanumérico completo', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Maria Silva',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    expect($attempt->access_code)->not->toBeNull();
    expect($attempt->access_code)->toMatch('/^ALU-[A-Z0-9]{10}$/');
    $suffix = substr($attempt->access_code, 4);
    expect(strlen($suffix))->toBe(10);
});

test('banco de dados rejeita duplicidade de access_code em student_attempts', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Aluno 1',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    expect(function () use ($attempt) {
        StudentAttempt::create([
            'classroom_id' => $this->classroom->id,
            'student_name' => 'Aluno 2',
            'started_at' => now(),
            'status' => 'in_progress',
            'access_code' => $attempt->access_code,
        ]);
    })->toThrow(QueryException::class);
});

test('normaliza a digitação do código do aluno com ou sem prefixo e em minúsculas', function () {
    expect(StudentAttempt::normalizeAccessCode('alu-abcdefghjk'))->toBe('ALU-ABCDEFGHJK');
    expect(StudentAttempt::normalizeAccessCode('ABCDEFGHJK'))->toBe('ALU-ABCDEFGHJK');
    expect(StudentAttempt::normalizeAccessCode('  alu - abc-def-gh-jk  '))->toBe('ALU-ABCDEFGHJK');
});

test('aluno retoma avaliação em andamento com código da sala e código de aluno', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'João Souza',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    $response = $this->post(route('estudante.retomar'), [
        'resume_code' => $this->classroom->code,
        'access_code' => $attempt->access_code,
    ]);

    $response->assertRedirect(route('estudante.player', ['code' => $this->classroom->code]));
    $response->assertSessionHas('current_attempt_id', $attempt->id);
    $response->assertSessionHas('current_classroom_code', $this->classroom->code);
    $response->assertSessionHas('info', 'Bem-vindo de volta! Você continua de onde parou.');
});

test('retomada em sala encerrada redireciona para resultado', function () {
    $this->classroom->update(['is_active' => false]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Carlos Lima',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    $response = $this->post(route('estudante.retomar'), [
        'resume_code' => $this->classroom->code,
        'access_code' => $attempt->access_code,
    ]);

    $response->assertRedirect(route('estudante.resultado', ['code' => $this->classroom->code]));
    $response->assertSessionHas('current_attempt_id', $attempt->id);
});

test('retomada com tentativa submetida e revisão coletiva ativa redireciona para revisão', function () {
    $this->classroom->update(['collective_review_enabled' => true]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Ana Clara',
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'submitted',
    ]);

    $response = $this->post(route('estudante.retomar'), [
        'resume_code' => $this->classroom->code,
        'access_code' => $attempt->access_code,
    ]);

    $response->assertRedirect(route('estudante.revisao', ['code' => $this->classroom->code]));
});

test('retomada com código inválido retorna erro de validação e consome rate limit', function () {
    $response = $this->from(route('estudante.entrar'))
        ->post(route('estudante.retomar'), [
            'resume_code' => $this->classroom->code,
            'access_code' => 'ALU-INVALID123',
        ]);

    $response->assertRedirect(route('estudante.entrar'));
    $response->assertSessionHasErrors(['access_code']);
});
