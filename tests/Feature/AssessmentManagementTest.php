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
        ->assertSee('2 acerto(s)')
        ->assertSee('Criada em:')
        ->assertSee('Aplicação:')
        ->assertSee(now()->format('d/m/Y'));
});

test('exibe data de criacao e data de aplicacao das salas nas avaliacoes em dia unico ou intervalo', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id, 'title' => 'Simulado de Matemática']);

    // Sala 1: aplicada em um único dia
    $roomSingleDay = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Único Dia',
        'code' => 'DIA-001',
    ]);
    StudentAttempt::create([
        'classroom_id' => $roomSingleDay->id,
        'student_name' => 'Aluno 1',
        'started_at' => now()->startOfDay()->addHours(8),
        'completed_at' => now()->startOfDay()->addHours(9),
    ]);

    expect($roomSingleDay->applicationDateFormatted())->toBe(now()->format('d/m/Y'));

    // Sala 2: aplicada em intervalo de dias
    $roomRangeDays = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Intervalo',
        'code' => 'RNG-002',
    ]);
    StudentAttempt::create([
        'classroom_id' => $roomRangeDays->id,
        'student_name' => 'Aluno 2',
        'started_at' => now()->subDays(3)->startOfDay()->addHours(10),
        'completed_at' => now()->subDays(3)->startOfDay()->addHours(11),
    ]);
    StudentAttempt::create([
        'classroom_id' => $roomRangeDays->id,
        'student_name' => 'Aluno 3',
        'started_at' => now()->startOfDay()->addHours(14),
        'completed_at' => now()->startOfDay()->addHours(15),
    ]);

    $expectedRange = now()->subDays(3)->format('d/m/Y').' até '.now()->format('d/m/Y');
    expect($roomRangeDays->applicationDateFormatted())->toBe($expectedRange);

    // Sala 3: ainda não aplicada
    $roomEmpty = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Vazia',
        'code' => 'VAZ-003',
    ]);
    expect($roomEmpty->applicationDateFormatted())->toBe('Não aplicada');

    // Validação na página Minhas Avaliações
    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.index'))
        ->assertOk()
        ->assertSee('Turma Único Dia')
        ->assertSee('Turma Intervalo')
        ->assertSee($roomSingleDay->created_at->format('d/m/Y'))
        ->assertSee($expectedRange)
        ->assertSee('Não aplicada');

    // Validação na página Gráficos e Desempenho da Avaliação
    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.estatisticas', $assessment))
        ->assertOk()
        ->assertSee('Turma Único Dia')
        ->assertSee('Turma Intervalo')
        ->assertSee($roomSingleDay->created_at->format('d/m/Y'))
        ->assertSee($expectedRange);
});

test('professor pode definir e editar o periodo letivo da avaliacao', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão de Período Letivo',
        'support_type' => 'none',
        'correct_option' => 'A',
    ]);

    // Criar avaliação com período letivo
    $response = $this->actingAs($professor)
        ->post(route('professor.avaliacoes.store'), [
            'title' => 'Avaliação Diagnóstica 2026',
            'academic_period' => '1º Bimestre',
            'description' => 'Avaliação inicial do ano letivo',
            'question_ids' => [$question->uuid],
        ]);

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('success');

    $assessment = Assessment::where('title', 'Avaliação Diagnóstica 2026')->first();
    expect($assessment)->not->toBeNull();
    expect($assessment->academic_period)->toBe('1º Bimestre');

    // Visualizar na listagem de avaliações
    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.index'))
        ->assertOk()
        ->assertSee('Avaliação Diagnóstica 2026')
        ->assertSee('1º Bimestre');

    // Visualizar no formulário de edição
    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.edit', $assessment))
        ->assertOk()
        ->assertSee('1º Bimestre');

    // Editar período letivo
    $updateResponse = $this->actingAs($professor)
        ->put(route('professor.avaliacoes.update', $assessment), [
            'title' => 'Avaliação Diagnóstica 2026 - Atualizada',
            'academic_period' => '2ª Etapa',
            'description' => 'Descrição atualizada',
            'question_ids' => [$question->uuid],
        ]);

    $updateResponse->assertRedirect(route('professor.avaliacoes.index'));
    $updateResponse->assertSessionHas('success');

    $assessment->refresh();
    expect($assessment->title)->toBe('Avaliação Diagnóstica 2026 - Atualizada');
    expect($assessment->academic_period)->toBe('2ª Etapa');

    // Visualizar nas estatísticas
    $this->actingAs($professor)
        ->get(route('professor.avaliacoes.estatisticas', $assessment))
        ->assertOk()
        ->assertSee('2ª Etapa');
});
