<?php

use App\Mail\AssessmentReportMail;
use App\Mail\ClassroomReportMail;
use App\Models\Assessment;
use App\Models\AssessmentShare;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionShare;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('professor cria sala no Modo Corujão e revisão coletiva é desativada compulsoriamente', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado Corujão 9º Ano',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Qual é a raiz de 144?',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $response = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => 'Turma 9A - Corujão',
        'assessment_id' => $assessment->id,
        'code' => 'CRJ-9001',
        'game_mode' => 'corujao',
        'collective_review_enabled' => '1', // mesmo se tentar enviar 1
    ]);

    $classroom = Classroom::where('code', 'CRJ-9001')->firstOrFail();

    expect($classroom->game_mode)->toBe('corujao')
        ->and($classroom->isCorujao())->toBeTrue()
        ->and($classroom->collective_review_enabled)->toBeFalse();

    $response->assertRedirect(route('professor.salas.show', $classroom));
});

test('aluno tem resposta salva automaticamente no timeout do Modo Corujão sem travar por falta de marca-texto', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado Corujão Timer',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Texto longo com marcação obrigatória',
        'support_type' => 'text',
        'support_text' => 'O texto base que exigiria marca-texto',
        'require_command_highlight' => true,
        'require_clue_highlight' => true,
        'correct_option' => 'B',
    ]);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'A', 'text' => 'Opção A']);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'B', 'text' => 'Opção B']);

    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Timer',
        'code' => 'TIMER-1',
        'game_mode' => 'corujao',
        'is_active' => true,
        'collective_review_enabled' => false,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Maria Silva',
        'student_code' => 'ALU-101',
        'started_at' => now(),
    ]);

    // Resposta enviada com is_timeout = true (sem nenhum highlight marcado)
    $response = $this->withSession([
        'current_attempt_id' => $attempt->id,
        'classroom_id' => $classroom->id,
    ])->postJson(route('estudante.salvar_resposta', $classroom->code), [
        'question_id' => $question->id,
        'answer' => 'A',
        'time_spent' => 60000,
        'is_timeout' => true,
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $savedAnswer = StudentAnswer::where('student_attempt_id', $attempt->id)
        ->where('question_id', $question->id)
        ->first();

    expect($savedAnswer)->not->toBeNull()
        ->and($savedAnswer->initial_answer)->toBe('A')
        ->and($savedAnswer->time_spent_seconds)->toBe(60);
});

test('professor pode exportar relatórios de sala de aula encerrada em PDF, XLSX, imagens e ZIP', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Final',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Pergunta para relatório',
        'correct_option' => 'C',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Encerrada',
        'code' => 'REL-001',
        'is_active' => false, // sala encerrada
        'game_mode' => 'corujinha',
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'João Souza',
        'student_code' => 'ALU-202',
        'started_at' => now()->subMinutes(10),
        'completed_at' => now(),
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'selected_option' => 'C',
        'is_correct' => true,
        'time_spent_seconds' => 45,
    ]);

    // Download PDF
    $pdfResponse = $this->actingAs($professor)
        ->get(route('professor.salas.relatorio.download', [$classroom, 'type' => 'pdf']));
    $pdfResponse->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    // Download XLSX
    $xlsxResponse = $this->actingAs($professor)
        ->get(route('professor.salas.relatorio.download', [$classroom, 'type' => 'xlsx']));
    $xlsxResponse->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // Download Imagens ZIP
    $imgResponse = $this->actingAs($professor)
        ->get(route('professor.salas.relatorio.download', [$classroom, 'type' => 'images']));
    $imgResponse->assertOk()
        ->assertHeader('content-type', 'application/zip');

    // Download Pacote Completo ZIP
    $zipResponse = $this->actingAs($professor)
        ->get(route('professor.salas.relatorio.download', [$classroom, 'type' => 'zip']));
    $zipResponse->assertOk()
        ->assertHeader('content-type', 'application/zip');
});

test('professor pode enviar relatório de sala de aula por e-mail com anexos adequados', function () {
    Mail::fake();

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active', 'email' => 'prof.relatorio@escola.com']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Diagnóstica',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão diagnóstica',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala 5B',
        'code' => 'REL-002',
        'is_active' => false,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Lucas Lima',
        'student_code' => 'ALU-303',
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'selected_option' => 'A',
        'is_correct' => true,
        'time_spent_seconds' => 30,
    ]);

    // Envio sem passar o parâmetro 'email', devendo usar o e-mail do professor autenticado como padrão
    $response = $this->actingAs($professor)
        ->post(route('professor.salas.relatorio.email', $classroom));

    $response->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(ClassroomReportMail::class, function ($mail) use ($professor) {
        return $mail->hasTo($professor->email);
    });
});

test('professor pode exportar e enviar relatório consolidado de Avaliação', function () {
    Mail::fake();

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Trimestral de Matemática',
    ]);

    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão de soma',
        'correct_option' => 'D',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma A',
        'code' => 'AVAL-01',
        'is_active' => false,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Ana Clara',
        'student_code' => 'ALU-404',
        'started_at' => now()->subMinutes(8),
        'completed_at' => now(),
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'selected_option' => 'D',
        'is_correct' => true,
        'time_spent_seconds' => 50,
    ]);

    // Download PDF da Avaliação
    $pdfResponse = $this->actingAs($professor)
        ->get(route('professor.avaliacoes.relatorio.download', [$assessment, 'type' => 'pdf']));
    $pdfResponse->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    // Download XLSX da Avaliação
    $xlsxResponse = $this->actingAs($professor)
        ->get(route('professor.avaliacoes.relatorio.download', [$assessment, 'type' => 'xlsx']));
    $xlsxResponse->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    // Enviar relatório por e-mail sem passar 'email' (usa o e-mail do professor autenticado como padrão)
    $emailResponse = $this->actingAs($professor)
        ->post(route('professor.avaliacoes.relatorio.email', $assessment));
    $emailResponse->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(AssessmentReportMail::class, function ($mail) use ($professor) {
        return $mail->hasTo($professor->email);
    });
});

test('professor pode compartilhar avaliação com colega e colega pode cloná-la', function () {
    $dono = User::factory()->create(['role' => 'professor', 'status' => 'active', 'email' => 'dono@escola.com']);
    $colega = User::factory()->create(['role' => 'professor', 'status' => 'active', 'email' => 'colega@escola.com']);

    $assessment = Assessment::create([
        'user_id' => $dono->id,
        'title' => 'Avaliação Compartilhável',
        'description' => 'Avaliação criada para compartilhar com os pares',
    ]);

    $question = Question::create([
        'user_id' => $dono->id,
        'prompt' => 'Questão modelo 1',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1, 'points' => 2.5]);

    // Dono compartilha com colega
    $response = $this->actingAs($dono)
        ->post(route('professor.avaliacoes.share', $assessment), [
            'email' => 'colega@escola.com',
            'permission' => 'view',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(AssessmentShare::where('assessment_id', $assessment->id)->where('user_id', $colega->id)->exists())->toBeTrue();

    // Colega vê na listagem de compartilhadas
    $listResponse = $this->actingAs($colega)
        ->get(route('professor.avaliacoes.index', ['tab' => 'compartilhadas']));
    $listResponse->assertOk()
        ->assertSee('Avaliação Compartilhável')
        ->assertSee($dono->name);

    // Colega clona a avaliação para sua conta
    $cloneResponse = $this->actingAs($colega)
        ->post(route('professor.avaliacoes.clone', $assessment));

    $cloneResponse->assertRedirect(route('professor.avaliacoes.index'))
        ->assertSessionHas('success');

    $clonedAssessment = Assessment::where('user_id', $colega->id)->firstOrFail();
    expect($clonedAssessment->title)->toContain('Cópia de Avaliação Compartilhável')
        ->and($clonedAssessment->questions()->count())->toBe(1);

    // Dono revoga o acesso
    $share = AssessmentShare::where('assessment_id', $assessment->id)->where('user_id', $colega->id)->firstOrFail();
    $revokeResponse = $this->actingAs($dono)
        ->delete(route('professor.avaliacoes.share.destroy', [$assessment, $share]));

    $revokeResponse->assertRedirect()
        ->assertSessionHas('success');

    expect(AssessmentShare::find($share->id))->toBeNull();
});

test('professor pode compartilhar pergunta individual com colega e colega pode cloná-la', function () {
    $dono = User::factory()->create(['role' => 'professor', 'status' => 'active', 'email' => 'autor@escola.com']);
    $colega = User::factory()->create(['role' => 'professor', 'status' => 'active', 'email' => 'parceiro@escola.com']);

    $question = Question::create([
        'user_id' => $dono->id,
        'prompt' => 'Qual o maior planeta do sistema solar?',
        'area' => 'Ciências da Natureza',
        'correct_option' => 'B',
        'support_type' => 'none',
    ]);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'A', 'text' => 'Marte']);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'B', 'text' => 'Júpiter']);

    // Dono compartilha com colega
    $response = $this->actingAs($dono)
        ->post(route('professor.perguntas.share', $question), [
            'email' => 'parceiro@escola.com',
            'permission' => 'edit',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    expect(QuestionShare::where('question_id', $question->id)->where('user_id', $colega->id)->exists())->toBeTrue();

    // Colega vê na listagem de compartilhadas
    $listResponse = $this->actingAs($colega)
        ->get(route('professor.perguntas.index', ['tab' => 'compartilhadas']));
    $listResponse->assertOk()
        ->assertSee('Qual o maior planeta do sistema solar?')
        ->assertSee($dono->name);

    // Colega clona a pergunta para seu banco
    $cloneResponse = $this->actingAs($colega)
        ->post(route('professor.perguntas.clone', $question));

    $cloneResponse->assertRedirect(route('professor.perguntas.index'))
        ->assertSessionHas('success');

    $clonedQuestion = Question::where('user_id', $colega->id)->firstOrFail();
    expect($clonedQuestion->prompt)->toBe('Qual o maior planeta do sistema solar?')
        ->and($clonedQuestion->options()->count())->toBe(2);

    // Dono revoga o acesso
    $share = QuestionShare::where('question_id', $question->id)->where('user_id', $colega->id)->firstOrFail();
    $revokeResponse = $this->actingAs($dono)
        ->delete(route('professor.perguntas.share.destroy', [$question, $share]));

    $revokeResponse->assertRedirect()
        ->assertSessionHas('success');

    expect(QuestionShare::find($share->id))->toBeNull();
});

test('aluno tem saida da prova registrada e contabilizada', function () {
    $professor = User::factory()->create(['role' => 'professor']);
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação com Monitoramento de Foco',
    ]);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão teste foco',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Monitorada',
        'code' => 'SWT-001',
        'is_active' => true,
        'game_mode' => 'corujinha',
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Distraído',
        'student_code' => 'ALU-SWT',
        'started_at' => now(),
        'tab_switches_count' => 0,
    ]);

    // Primeira saída da aba
    $response = $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.registrar_saida', ['code' => $classroom->code]), [
            'reason' => 'visibility_hidden',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'tab_switches_count' => 1,
        ]);

    // Segunda saída da aba
    $response2 = $this->withSession(['current_attempt_id' => $attempt->id])
        ->postJson(route('estudante.registrar_saida', ['code' => $classroom->code]), [
            'reason' => 'window_blur',
        ]);

    $response2->assertOk()
        ->assertJson([
            'success' => true,
            'tab_switches_count' => 2,
        ]);

    $attempt->refresh();
    expect($attempt->tab_switches_count)->toBe(2)
        ->and($attempt->tab_switches_log)->toBeArray()
        ->and(count($attempt->tab_switches_log))->toBe(2)
        ->and($attempt->tab_switches_log[0]['reason'])->toBe('visibility_hidden')
        ->and($attempt->tab_switches_log[1]['reason'])->toBe('window_blur');
});
