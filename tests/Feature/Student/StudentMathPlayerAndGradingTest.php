<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $this->assessment = Assessment::create([
        'user_id' => $this->professor->id,
        'title' => 'Avaliação de Matemática 9º Ano',
    ]);

    $this->mathQuestion = Question::create([
        'user_id' => $this->professor->id,
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Resolva a equação $2x - 1 = 6$. Qual o valor de $x$?',
        'expected_answer' => '3.5',
        'expected_answer_explanation' => 'Somando 1 aos dois lados temos 2x = 7, logo x = 7/2 = 3.5',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
    ]);

    $this->assessment->questions()->attach($this->mathQuestion->id, [
        'order' => 1,
        'points' => 2.5,
    ]);

    $this->classroom = Classroom::create([
        'user_id' => $this->professor->id,
        'assessment_id' => $this->assessment->id,
        'name' => 'Turma 9º A - Matemática',
        'code' => 'MATH-9901',
        'is_active' => true,
    ]);
});

test('student can answer a direct input math question with resolution photo and receive auto-correction', function () {
    // 1. Aluno entra na sala
    $joinResponse = $this->post(route('estudante.iniciar'), [
        'code' => 'MATH-9901',
        'student_name' => 'Maria Silva',
    ]);

    $joinResponse->assertRedirect(route('estudante.player', $this->classroom->code));
    $attempt = StudentAttempt::where('classroom_id', $this->classroom->id)->first();
    expect($attempt)->not->toBeNull();

    // 2. Aluno envia resposta equivalente (3,5 em vez de 3.5) com foto da folha de cálculo
    $fakeImage = UploadedFile::fake()->image('minha_resolucao.jpg', 600, 800);

    $answerResponse = $this->post(route('estudante.salvar_resposta', $this->classroom->code), [
        'question_id' => $this->mathQuestion->id,
        'answer' => '3,5',
        'resolution_image' => $fakeImage,
        'time_spent_seconds' => 45,
    ]);

    $answerResponse->assertOk();
    $answerResponse->assertJson(['success' => true]);

    $studentAnswer = StudentAnswer::where('student_attempt_id', $attempt->id)
        ->where('question_id', $this->mathQuestion->id)
        ->first();

    expect($studentAnswer)->not->toBeNull()
        ->and($studentAnswer->initial_answer)->toBe('3,5')
        ->and($studentAnswer->is_correct_initial)->toBeTrue()
        ->and((float) $studentAnswer->score)->toBe(2.5)
        ->and($studentAnswer->hasResolutionImage())->toBeTrue();

    Storage::disk('local')->assertExists($studentAnswer->resolution_image_path);

    // 3. Imagem pode ser visualizada via rota protegida pelo professor
    $mediaResponse = $this->actingAs($this->professor)
        ->get(route('midia.resolucao', $studentAnswer));

    $mediaResponse->assertOk();
});

test('teacher can manually grade a student answer and adjust score and feedback', function () {
    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'João Pedro',
        'started_at' => now()->subMinutes(10),
        'completed_at' => now()->subMinutes(2),
        'status' => 'completed',
    ]);

    $studentAnswer = StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $this->mathQuestion->id,
        'initial_answer' => 'x = 3',
        'is_correct_initial' => false,
        'is_correct_final' => false,
        'score' => 0.0,
        'time_spent_seconds' => 60,
    ]);

    $attempt->recalculateScore();
    expect($attempt->fresh()->score)->toBe(0)
        ->and((float) $attempt->fresh()->earnedPoints())->toBe(0.0);

    // Professor visualiza a tela de detalhes da tentativa
    $viewResponse = $this->actingAs($this->professor)
        ->get(route('professor.salas.tentativa', [$this->classroom, $attempt]));

    $viewResponse->assertOk();
    $viewResponse->assertSee('João Pedro');
    $viewResponse->assertSee('Resolva a equação');
    $viewResponse->assertSee('x = 3');

    // Professor corrige manualmente atribuindo nota parcial de 1.5 com devolutiva
    $gradeResponse = $this->actingAs($this->professor)
        ->post(route('professor.salas.respostas.corrigir', [$this->classroom, $attempt, $studentAnswer]), [
            'manual_score' => 1.5,
            'is_correct' => 'partial',
            'teacher_comment' => 'Você montou a equação perfeitamente, mas somou errado na etapa final.',
        ]);

    $gradeResponse->assertRedirect();
    $gradeResponse->assertSessionHas('success');

    $studentAnswer->refresh();
    expect($studentAnswer->is_manually_graded)->toBeTrue()
        ->and((float) $studentAnswer->manual_score)->toBe(1.5)
        ->and((float) $studentAnswer->score)->toBe(1.5)
        ->and($studentAnswer->teacher_comment)->toContain('montou a equação perfeitamente');

    // A pontuação final da tentativa reflete os pontos concedidos pelo professor
    $freshAttempt = $attempt->fresh();
    expect((float) $freshAttempt->earnedPoints())->toBe(1.5);
});

test('student sees mathematical formulas, resolution and teacher comment on results page', function () {
    $expectedImg = UploadedFile::fake()->image('prof_resolucao.png')->store('questions/expected_answers', 'local');
    $this->mathQuestion->update(['expected_answer_image_path' => $expectedImg]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $this->classroom->id,
        'student_name' => 'Lucas Ferreira',
        'started_at' => now()->subMinutes(15),
        'completed_at' => now()->subMinutes(5),
        'status' => 'completed',
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $this->mathQuestion->id,
        'initial_answer' => '3.5',
        'is_correct_initial' => true,
        'is_correct_final' => true,
        'score' => 2.5,
        'is_manually_graded' => true,
        'manual_score' => 2.5,
        'teacher_comment' => 'Parabéns, demonstração impecável!',
    ]);

    $this->classroom->update(['is_active' => false]);

    // Simula sessão do estudante logado
    $response = $this->withSession([
        'current_attempt_id' => $attempt->id,
    ])->get(route('estudante.resultado', $this->classroom->code));

    $response->assertOk();
    $response->assertSee('Gabarito & Devolutivas Pedagógicas', false);
    $response->assertSee('Parabéns, demonstração impecável!');
    $response->assertSee('data-math', false);
    $response->assertSee('Foto / Esquema da Resolução do Professor');

    // Estudante com a tentativa ativa pode acessar a imagem da resolução esperada
    $imgResponse = $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('midia.questao.resolucao_esperada', $this->mathQuestion));

    $imgResponse->assertOk();
});
