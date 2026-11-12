<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor consegue excluir sua propria avaliacao quando nao ha salas vinculadas', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    $response = $this->actingAs($professor)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('success', 'Avaliação excluída com sucesso.');

    expect(Assessment::find($assessment->id))->toBeNull();
});

test('professor nao consegue excluir avaliacao vinculada a sala de aula', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma 9º Ano A',
        'code' => 'TEST99',
        'status' => 'open',
    ]);

    $response = $this->actingAs($professor)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('error');

    expect(Assessment::find($assessment->id))->not->toBeNull();
});

test('professor nao pode excluir avaliacao pertencente a outro professor', function () {
    $professor1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $professor2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor1->id]);

    $response = $this->actingAs($professor2)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertForbidden();
    expect(Assessment::find($assessment->id))->not->toBeNull();
});

test('super admin pode excluir avaliacao de qualquer professor se nao tiver salas', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    $response = $this->actingAs($admin)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('success');

    expect(Assessment::find($assessment->id))->toBeNull();
});

test('professor visualiza graficos comparativos de salas e questoes mais acertadas e erradas na avaliacao', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id, 'title' => 'Simulado Estatístico']);

    $q1 = Question::create(['user_id' => $professor->id, 'prompt' => 'Questão Fácil de Interpretação', 'support_type' => 'none', 'correct_option' => 'A']);
    $q2 = Question::create(['user_id' => $professor->id, 'prompt' => 'Questão Difícil de Inferência', 'support_type' => 'none', 'correct_option' => 'B']);
    $assessment->questions()->attach([$q1->id => ['order' => 1], $q2->id => ['order' => 2]]);

    $roomA = Classroom::create(['user_id' => $professor->id, 'assessment_id' => $assessment->id, 'name' => 'Turma Alfa', 'code' => 'ALFA-1']);
    $roomB = Classroom::create(['user_id' => $professor->id, 'assessment_id' => $assessment->id, 'name' => 'Turma Beta', 'code' => 'BETA-1']);

    $attA = StudentAttempt::create(['classroom_id' => $roomA->id, 'student_name' => 'Aluno Alfa', 'started_at' => now()]);
    StudentAnswer::create(['student_attempt_id' => $attA->id, 'question_id' => $q1->id, 'initial_answer' => 'A', 'is_correct_initial' => true]);
    StudentAnswer::create(['student_attempt_id' => $attA->id, 'question_id' => $q2->id, 'initial_answer' => 'A', 'is_correct_initial' => false]);

    $attB = StudentAttempt::create(['classroom_id' => $roomB->id, 'student_name' => 'Aluno Beta', 'started_at' => now()]);
    StudentAnswer::create(['student_attempt_id' => $attB->id, 'question_id' => $q1->id, 'initial_answer' => 'A', 'is_correct_initial' => true]);
    StudentAnswer::create(['student_attempt_id' => $attB->id, 'question_id' => $q2->id, 'initial_answer' => 'B', 'is_correct_initial' => true]);

    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.estatisticas', $assessment))
        ->assertOk()
        ->assertSee('Ranking de Acertos por Sala de Aula')
        ->assertSee('Questões Mais Acertadas')
        ->assertSee('Questões Mais Erradas')
        ->assertSee('Turma Beta')
        ->assertSee('Turma Alfa')
        ->assertSee('Questão Fácil de Interpretação')
        ->assertSee('Questão Difícil de Inferência')
        ->assertSee('2 acerto(s)');
});
