<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);

    $this->assessment = Assessment::create([
        'user_id' => $this->professor->id,
        'title' => 'Avaliação de Agendamento',
    ]);

    $question = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Questão teste',
        'correct_option' => 'A',
    ]);

    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
        QuestionOption::create([
            'question_id' => $question->id,
            'letter' => $letter,
            'text' => "Opção $letter",
        ]);
    }

    $this->assessment->questions()->attach($question->id, ['order' => 1, 'points' => 1.0]);
});

test('professor pode criar sala com data e hora de abertura e fechamento', function () {
    $startsAt = now()->addHour()->format('Y-m-d\TH:i');
    $endsAt = now()->addDays(2)->format('Y-m-d\TH:i');

    $response = $this->actingAs($this->professor)->post(route('professor.salas.store'), [
        'name' => 'Turma Lição de Casa',
        'assessment_id' => $this->assessment->uuid,
        'code' => 'SALA-SCHED1',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $response->assertRedirect();

    $classroom = Classroom::where('code', 'SALA-SCHED1')->first();
    expect($classroom)->not->toBeNull()
        ->and($classroom->starts_at)->not->toBeNull()
        ->and($classroom->ends_at)->not->toBeNull();
});

test('impede data de fechamento anterior ou igual a data de abertura', function () {
    $startsAt = now()->addDays(2)->format('Y-m-d\TH:i');
    $endsAt = now()->addHour()->format('Y-m-d\TH:i');

    $response = $this->actingAs($this->professor)->post(route('professor.salas.store'), [
        'name' => 'Turma Erro',
        'assessment_id' => $this->assessment->uuid,
        'code' => 'SALA-ERR1',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $response->assertSessionHasErrors('ends_at');
});

test('aluno é impedido de entrar em sala que ainda não abriu', function () {
    $classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Sala Futura',
        'code' => 'FUTUR-001',
        'is_active' => true,
        'starts_at' => now()->addHours(3),
        'ends_at' => now()->addDays(2),
    ]);

    $response = $this->post(route('estudante.iniciar'), [
        'code' => 'FUTUR-001',
        'student_name' => 'Aluno Teste',
    ]);

    $response->assertSessionHasErrors('code');
    $errors = session('errors')->get('code');
    expect($errors[0])->toContain('Esta sala de aula ainda não foi aberta');
});

test('aluno é impedido de entrar em sala cujo prazo já encerrou', function () {
    $classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Sala Expirada',
        'code' => 'EXPIR-001',
        'is_active' => true,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subHour(),
    ]);

    $response = $this->post(route('estudante.iniciar'), [
        'code' => 'EXPIR-001',
        'student_name' => 'Aluno Teste',
    ]);

    $response->assertSessionHasErrors('code');
    $errors = session('errors')->get('code');
    expect($errors[0])->toContain('O prazo para realização desta atividade encerrou');
});

test('se preenchido apenas o fechamento, sala fica aberta da criação até o encerramento', function () {
    $classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Sala com Apenas Fechamento',
        'code' => 'FECHA-001',
        'is_active' => true,
        'starts_at' => null,
        'ends_at' => now()->addDays(1),
    ]);

    expect($classroom->isOpenNow())->toBeTrue();

    $response = $this->post(route('estudante.iniciar'), [
        'code' => 'FECHA-001',
        'student_name' => 'Aluno Entrando',
    ]);

    $response->assertRedirect(route('estudante.player', ['code' => 'FECHA-001']));
});
