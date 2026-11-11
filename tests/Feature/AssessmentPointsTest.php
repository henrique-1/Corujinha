<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor pode definir pontuacao especifica para cada pergunta na avaliacao', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $q1 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 1 (fácil)',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    $q2 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 2 (difícil)',
        'support_type' => 'text',
        'correct_option' => 'D',
    ]);

    $response = $this->post(route('professor.avaliacoes.store'), [
        'title' => 'Simulado com Pesos Diferentes',
        'question_ids' => [$q1->id, $q2->id],
        'points' => [
            $q1->id => 2.5,
            $q2->id => 7.5,
        ],
    ]);

    $response->assertRedirect(route('professor.avaliacoes.index'));

    $assessment = Assessment::where('title', 'Simulado com Pesos Diferentes')->first();
    expect($assessment)->not->toBeNull();
    expect($assessment->totalPoints())->toBe(10.0);

    $this->assertDatabaseHas('assessment_question', [
        'assessment_id' => $assessment->id,
        'question_id' => $q1->id,
        'points' => 2.50,
    ]);

    $this->assertDatabaseHas('assessment_question', [
        'assessment_id' => $assessment->id,
        'question_id' => $q2->id,
        'points' => 7.50,
    ]);
});

test('calcula pontuacao ponderada obtida pelo estudante conforme peso das questoes', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $q1 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 1',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    $q2 = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 2',
        'support_type' => 'text',
        'correct_option' => 'B',
    ]);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Ponderada',
    ]);

    $assessment->questions()->attach([
        $q1->id => ['order' => 1, 'points' => 3.0],
        $q2->id => ['order' => 2, 'points' => 7.0],
    ]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma de Teste',
        'code' => 'PONTOS99',
        'is_active' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Lucas Aluno',
        'started_at' => now(),
        'status' => 'completed',
    ]);

    // Aluno acertou q1 (3.0 pts) e errou q2 (7.0 pts)
    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $q1->id,
        'initial_answer' => 'A',
        'is_correct_initial' => true,
        'is_correct_final' => true,
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $q2->id,
        'initial_answer' => 'C', // Incorreto
        'is_correct_initial' => false,
        'is_correct_final' => false,
    ]);

    $attempt->recalculateScore();

    expect($attempt->score)->toBe(1); // 1 acerto de 2
    expect($attempt->earnedPoints())->toBe(3.0);
    expect($attempt->totalPossiblePoints())->toBe(10.0);
});

test('aceita ate duas casas decimais na pontuacao e rejeita mais de duas casas ou valor acima de 99.99', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão Decimal',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    // 3 casas decimais devem falhar na validação
    $invalidDecimalResponse = $this->post(route('professor.avaliacoes.store'), [
        'title' => 'Avaliação Inválida 3 Casas',
        'question_ids' => [$question->id],
        'points' => [
            $question->id => '1.234',
        ],
    ]);
    $invalidDecimalResponse->assertSessionHasErrors("points.{$question->id}");

    // Acima de 99.99 deve falhar na validação
    $invalidMaxResponse = $this->post(route('professor.avaliacoes.store'), [
        'title' => 'Avaliação Inválida Max',
        'question_ids' => [$question->id],
        'points' => [
            $question->id => '100.00',
        ],
    ]);
    $invalidMaxResponse->assertSessionHasErrors("points.{$question->id}");

    // Até 2 casas decimais (ex: 1.75) deve ser salva com sucesso
    $validResponse = $this->post(route('professor.avaliacoes.store'), [
        'title' => 'Avaliação Válida Decimal',
        'question_ids' => [$question->id],
        'points' => [
            $question->id => '1.75',
        ],
    ]);
    $validResponse->assertRedirect(route('professor.avaliacoes.index'));

    $assessment = Assessment::where('title', 'Avaliação Válida Decimal')->first();
    expect($assessment)->not->toBeNull();
    expect($assessment->totalPoints())->toBe(1.75);
});
