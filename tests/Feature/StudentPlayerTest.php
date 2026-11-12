<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $this->assessment = Assessment::create([
        'user_id' => $this->professor->id,
        'title' => 'Avaliação Diagnóstica',
    ]);

    $this->question = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'O adjetivo contribui para',
        'support_type' => 'text',
        'support_text' => 'Tarzan mostrou ser uma grata surpresa.',
        'require_command_highlight' => true,
        'require_clue_highlight' => true,
        'correct_option' => 'B',
    ]);

    QuestionOption::create([
        'question_id' => $this->question->id,
        'letter' => 'A',
        'text' => 'Opção A',
        'feedback' => 'Devolutiva A',
    ]);
    QuestionOption::create([
        'question_id' => $this->question->id,
        'letter' => 'B',
        'text' => 'Opção B',
        'feedback' => 'Devolutiva B',
    ]);

    $this->assessment->questions()->attach($this->question->id, ['order' => 1]);

    $this->classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Sala Teste 7º B',
        'code' => 'TEST-7B01',
        'collective_review_enabled' => true,
        'is_active' => true,
    ]);
});

test('estudante entra na sala fornecendo código e nome completo', function () {
    $response = $this->post(route('estudante.iniciar'), [
        'code' => 'test-7b01',
        'student_name' => 'Lucas Ferreira',
    ]);

    $response->assertRedirect(route('estudante.player', ['code' => 'TEST-7B01']));

    $attempt = StudentAttempt::where('classroom_id', $this->classroom->id)->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->student_name)->toBe('Lucas Ferreira')
        ->and(session('current_attempt_id'))->toBe($attempt->id);
});

test('submissão de resposta exige marca-texto quando configurado na pergunta', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Mariana Lima',
        'started_at' => now(),
    ]);

    // Tentativa sem marca-texto deve falhar (422) pois question exige comando e pistas
    $response = $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $this->classroom->code), [
            'question_id' => $this->question->id,
            'answer' => 'B',
            'reason' => 'Escolhi B porque é a melhor alternativa.',
            'markers' => [],
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['error']);

    // Tentativa com os marcadores de comando e pista obrigatórios
    $successResponse = $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $this->classroom->code), [
            'question_id' => $this->question->id,
            'answer' => 'B',
            'reason' => 'Escolhi B porque a pista no texto mostra isso.',
            'markers' => [
                ['id' => 'm1', 'kind' => 'command', 'target' => 'prompt', 'start' => 0, 'end' => 10],
                ['id' => 'm2', 'kind' => 'evidence', 'target' => 'source', 'start' => 0, 'end' => 6],
            ],
            'time_spent' => 45000,
        ]);

    $successResponse->assertOk()
        ->assertJson(['success' => true]);
});

test('fluxo direciona para revisão quando habilitada e para resultado quando desabilitada', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Gabriel Santos',
        'started_at' => now(),
    ]);

    // Com revisão coletiva habilitada
    $resWithReview = $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_atividade', $this->classroom->code));

    $resWithReview->assertRedirect(route('estudante.revisao', $this->classroom->code));

    // Desabilita revisão coletiva na sala
    $this->classroom->update(['collective_review_enabled' => false]);

    $resWithoutReview = $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_atividade', $this->classroom->code));

    $resWithoutReview->assertRedirect(route('estudante.resultado', $this->classroom->code));
});

test('estudante que seleciona comando e pista e acerta a alternativa tem resposta computada como correta e visualiza no resultado', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Ana Clara',
        'started_at' => now(),
    ]);

    // Salva resposta B (que é a correta) com marcadores de comando e pista
    $saveResponse = $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $this->classroom->code), [
            'question_id' => $this->question->id,
            'answer' => 'b', // testa com minúscula para garantir normalização
            'reason' => 'Justificativa da Ana Clara sobre a resposta',
            'markers' => [
                ['id' => 'm1', 'kind' => 'command', 'target' => 'prompt', 'text' => 'adjetivo contribui', 'start' => 2, 'end' => 20],
                ['id' => 'm2', 'kind' => 'evidence', 'target' => 'support', 'text' => 'grata surpresa', 'start' => 22, 'end' => 36],
            ],
            'time_spent' => 30000,
        ]);

    $saveResponse->assertOk();

    // Conclui etapa de resolução antes da revisão coletiva
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_atividade', $this->classroom->code))
        ->assertRedirect(route('estudante.revisao', $this->classroom->code));

    // Finaliza revisão mantendo B
    $finishResponse = $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $this->classroom->code), [
            'reviews' => [
                $this->question->id => [
                    'reviewed_answer' => 'B',
                    'review_reflection' => 'Mantive a alternativa B após a explicação do professor',
                ],
            ],
        ]);

    $finishResponse->assertRedirect(route('estudante.resultado', $this->classroom->code));

    $attempt->refresh();
    expect($attempt->score)->toBe(1);

    // Acessa a tela de resultado
    $resultView = $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.resultado', $this->classroom->code));

    $resultView->assertOk()
        ->assertSee('Você Acertou')
        ->assertSee('1 / 1')
        ->assertSee('Sua Justificativa Inicial:')
        ->assertSee('Justificativa da Ana Clara sobre a resposta')
        ->assertSee('Sua Reflexão na Correção Coletiva:')
        ->assertSee('Mantive a alternativa B após a explicação do professor')
        ->assertSee('grata surpresa')
        ->assertSee('adjetivo contribui');
});

test('registra tempo utilizado acima do limite e exibe no historico do professor', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Beatriz Silva',
        'started_at' => now(),
    ]);

    // Limite padrão da questão é 180s (03:00). Aluno utilizou 245s (04:05 -> -01:05 no timer).
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $this->classroom->code), [
            'question_id' => $this->question->id,
            'answer' => 'B',
            'reason' => 'Demorei mais para reler o texto.',
            'markers' => [
                ['id' => 'm1', 'kind' => 'command', 'target' => 'prompt', 'start' => 0, 'end' => 10],
                ['id' => 'm2', 'kind' => 'evidence', 'target' => 'source', 'start' => 0, 'end' => 6],
            ],
            'time_spent' => 245000,
        ])
        ->assertOk();

    $this->assertDatabaseHas('student_answers', [
        'student_attempt_id' => $attempt->id,
        'question_id' => $this->question->id,
        'time_spent_seconds' => 245,
    ]);

    $this->actingAs($this->professor)
        ->get(route('professor.salas.tentativa', [$this->classroom, $attempt]))
        ->assertOk()
        ->assertSee('Tempo Total nas Questões')
        ->assertSee('04:05')
        ->assertSee('Tempo: 04:05 / Limite: 03:00')
        ->assertSee('(+01:05)');
});

test('sala encerrada bloqueia saveAnswer submitAttempt e finishReview redirecionando para devolutiva', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Carlos Aluno',
        'started_at' => now(),
    ]);

    $this->classroom->update(['is_active' => false]);

    $expectedRedirect = route('estudante.resultado', $this->classroom->code, false);

    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.salvar_resposta', $this->classroom->code), [
            'question_id' => $this->question->id,
            'answer' => 'B',
            'reason' => 'Tentativa tardia',
        ])
        ->assertStatus(422)
        ->assertJson([
            'closed' => true,
            'redirect_url' => $expectedRedirect,
        ]);

    $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.concluir_atividade', $this->classroom->code))
        ->assertStatus(422)
        ->assertJson([
            'closed' => true,
            'redirect_url' => $expectedRedirect,
        ]);

    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $this->classroom->code), [
            'reviews' => [],
        ])
        ->assertRedirect($expectedRedirect)
        ->assertSessionHas('info');
});

test('estudante pode alterar ate max_review_changes questoes na revisao coletiva e e bloqueado ao exceder o limite', function () {
    $q2 = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Segunda questão da prova',
        'support_type' => 'none',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'correct_option' => 'A',
    ]);
    QuestionOption::create(['question_id' => $q2->id, 'letter' => 'A', 'text' => 'Q2 A', 'feedback' => 'OK']);
    QuestionOption::create(['question_id' => $q2->id, 'letter' => 'B', 'text' => 'Q2 B', 'feedback' => 'Errado']);
    $this->assessment->questions()->attach($q2->id, ['order' => 2]);

    $this->classroom->update([
        'collective_review_enabled' => true,
        'max_review_changes' => 1,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Fernanda Limite',
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'submitted',
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $this->question->id,
        'initial_answer' => 'A',
        'initial_reason' => 'Inicial Q1',
    ]);
    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $q2->id,
        'initial_answer' => 'B',
        'initial_reason' => 'Inicial Q2',
    ]);

    // Tenta alterar as 2 questões quando limite é 1 -> deve falhar
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $this->classroom->code), [
            'reviews' => [
                $this->question->id => ['reviewed_answer' => 'B', 'review_reflection' => 'Alterei Q1'],
                $q2->id => ['reviewed_answer' => 'A', 'review_reflection' => 'Alterei Q2'],
            ],
        ])
        ->assertSessionHasErrors(['reviews']);

    // Altera apenas 1 questão e escreve reflexão nas duas -> sucesso
    $this->withSession(['current_attempt_id' => $attempt->id])
        ->post(route('estudante.concluir_revisao', $this->classroom->code), [
            'reviews' => [
                $this->question->id => ['reviewed_answer' => 'B', 'review_reflection' => 'Alterei apenas Q1'],
                $q2->id => ['reviewed_answer' => 'B', 'review_reflection' => 'Mantive Q2 mas escrevi reflexão'],
            ],
        ])
        ->assertRedirect(route('estudante.resultado', $this->classroom->code));

    expect($attempt->fresh()->score)->toBe(1);
});
