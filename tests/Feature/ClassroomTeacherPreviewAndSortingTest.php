<?php

use App\Events\StudentJoinedClassroom;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->otherProfessor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $this->assessment = Assessment::create([
        'user_id' => $this->professor->id,
        'title' => 'Simulado de Matemática',
    ]);

    $this->question1 = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Quanto é 2 + 2?',
        'support_type' => 'text',
        'support_text' => 'Texto sobre adição básica.',
        'correct_option' => 'A',
    ]);

    QuestionOption::create([
        'question_id' => $this->question1->id,
        'letter' => 'A',
        'text' => '4',
    ]);

    $this->assessment->questions()->attach($this->question1->id, ['order' => 1]);

    $this->classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Turma 8º C',
        'code' => 'AVAL-TEST8C',
        'is_active' => true,
    ]);
});

test('professor consegue visualizar a prova da sala com avanço livre', function () {
    $response = $this->actingAs($this->professor)
        ->get(route('professor.salas.preview', $this->classroom));

    $response->assertOk();
    $response->assertViewIs('estudante.player');
    $response->assertViewHas('isTeacherPreview', true);
    $response->assertViewHas('classroom');
    $response->assertSee('Visualização do Professor (Avanço Livre)');
    $response->assertSee('Voltar à Sala');
    $response->assertSee('Simulado de Matemática');
    expect($response->viewData('questionsData')[0]['prompt'])->toBe('Quanto é 2 + 2?');
});

test('outro professor não pode visualizar prova de sala que não lhe pertence', function () {
    $response = $this->actingAs($this->otherProfessor)
        ->get(route('professor.salas.preview', $this->classroom));

    $response->assertForbidden();
});

test('tabela de estudantes na tela do professor exibe o código do aluno', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Beatriz Santos',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->professor)
        ->get(route('professor.salas.show', $this->classroom));

    $response->assertOk();
    $response->assertSee('Código do Aluno');
    $response->assertSee($attempt->access_code);
    $response->assertSee('Beatriz Santos');
});

test('professor pode filtrar estudantes por nome', function () {
    StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Alice Ferreira',
        'started_at' => now()->subMinutes(10),
        'status' => 'in_progress',
    ]);

    StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Bernardo Souza',
        'started_at' => now()->subMinutes(5),
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->professor)
        ->get(route('professor.salas.show', [$this->classroom, 'search' => 'Alice']));

    $response->assertOk();
    $response->assertSee('Alice Ferreira');
    $response->assertDontSee('Bernardo Souza');
});

test('professor pode ordenar estudantes por nome e acertos', function () {
    $att1 = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Zélia Rocha',
        'started_at' => now()->subMinutes(10),
        'score' => 10,
        'status' => 'submitted',
    ]);

    $att2 = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Arthur Dias',
        'started_at' => now()->subMinutes(5),
        'score' => 2,
        'status' => 'submitted',
    ]);

    // Ordenação por nome ASC
    $responseNomeAsc = $this->actingAs($this->professor)
        ->get(route('professor.salas.show', [$this->classroom, 'sort' => 'nome', 'direction' => 'asc']));

    $responseNomeAsc->assertOk();
    $attemptsNome = $responseNomeAsc->viewData('attempts');
    expect($attemptsNome->first()->id)->toBe($att2->id)
        ->and($attemptsNome->last()->id)->toBe($att1->id);

    // Ordenação por acertos DESC
    $responseAcertosDesc = $this->actingAs($this->professor)
        ->get(route('professor.salas.show', [$this->classroom, 'sort' => 'acertos', 'direction' => 'desc']));

    $responseAcertosDesc->assertOk();
    $attemptsAcertos = $responseAcertosDesc->viewData('attempts');
    expect($attemptsAcertos->first()->id)->toBe($att1->id)
        ->and($attemptsAcertos->last()->id)->toBe($att2->id);
});

test('evento StudentJoinedClassroom inclui o access_code no payload', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Gabriel Ramos',
        'started_at' => now(),
        'status' => 'in_progress',
    ]);

    $event = new StudentJoinedClassroom($attempt);
    $payload = $event->broadcastWith();

    expect($payload['attempt']['access_code'])->toBe($attempt->access_code);
});
