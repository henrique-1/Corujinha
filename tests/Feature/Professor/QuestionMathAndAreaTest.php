<?php

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);
});

test('professor can create a math question with direct input and formula', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Resolva a equação $2x + 4 = 10$',
        'expected_answer' => 'x = 3',
        'expected_answer_explanation' => 'Subtraindo 4 temos 2x = 6, logo x = 3',
        'time_limit_seconds' => 120,
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('questions', [
        'user_id' => $this->professor->id,
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'expected_answer' => 'x = 3',
        'expected_answer_explanation' => 'Subtraindo 4 temos 2x = 6, logo x = 3',
        'correct_option' => null,
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
    ]);
});

test('math question with direct input fails validation without expected answer', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Qual é a raiz quadrada de 144?',
        'expected_answer' => '',
    ]);

    $response->assertSessionHasErrors(['expected_answer']);
});

test('professor can create a math question with multiple choice options', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'answer_type' => 'multiple_choice',
        'prompt' => 'Quanto é $5^2$?',
        'correct_option' => 'C',
        'options' => [
            ['letter' => 'A', 'text' => '10', 'feedback' => 'Você multiplicou por 2'],
            ['letter' => 'B', 'text' => '20', 'feedback' => 'Incorreto'],
            ['letter' => 'C', 'text' => '25', 'feedback' => 'Correto! 5 x 5 = 25'],
            ['letter' => 'D', 'text' => '30', 'feedback' => 'Incorreto'],
            ['letter' => 'E', 'text' => '50', 'feedback' => 'Incorreto'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $this->professor->id,
        'area' => 'Matemática',
        'answer_type' => 'multiple_choice',
        'correct_option' => 'C',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
    ]);

    $this->assertDatabaseCount('question_options', 5);
});

test('non-portuguese question has highlight requirements disabled by default', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'História',
        'prompt' => 'Em que ano ocorreu a Proclamação da República?',
        'correct_option' => 'A',
        'require_command_highlight' => '1',
        'require_clue_highlight' => '1',
        'expected_commands' => 'Proclamação da República',
        'expected_clues' => '1889',
        'options' => [
            ['letter' => 'A', 'text' => '1889', 'feedback' => 'Correto!'],
            ['letter' => 'B', 'text' => '1822', 'feedback' => 'Independência'],
            ['letter' => 'C', 'text' => '1500', 'feedback' => 'Descobrimento'],
            ['letter' => 'D', 'text' => '1930', 'feedback' => 'Era Vargas'],
            ['letter' => 'E', 'text' => '1988', 'feedback' => 'Constituição'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $this->professor->id,
        'area' => 'História',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'expected_commands' => null,
        'expected_clues' => null,
    ]);
});

test('empty area question behaves as simplified test with highlight requirements disabled', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => '',
        'prompt' => 'Qual é a capital do Brasil?',
        'correct_option' => 'A',
        'require_command_highlight' => '1',
        'require_clue_highlight' => '1',
        'options' => [
            ['letter' => 'A', 'text' => 'Brasília', 'feedback' => 'Correto!'],
            ['letter' => 'B', 'text' => 'Rio de Janeiro', 'feedback' => 'Antiga capital'],
            ['letter' => 'C', 'text' => 'Salvador', 'feedback' => 'Primeira capital'],
            ['letter' => 'D', 'text' => 'São Paulo', 'feedback' => 'Incorreto'],
            ['letter' => 'E', 'text' => 'Belo Horizonte', 'feedback' => 'Incorreto'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $this->professor->id,
        'area' => null,
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
        'expected_commands' => null,
        'expected_clues' => null,
    ]);
});

test('portuguese question preserves highlight requirements and clues', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'Língua Portuguesa',
        'prompt' => 'Identifique o sujeito da oração: "Chegaram os convidados."',
        'correct_option' => 'B',
        'require_command_highlight' => '1',
        'require_clue_highlight' => '1',
        'expected_commands' => 'Identifique o sujeito',
        'command_feedback' => 'O comando pede a identificação do sujeito.',
        'expected_clues' => 'os convidados',
        'clue_feedback' => 'Quem chegou foram os convidados.',
        'options' => [
            ['letter' => 'A', 'text' => 'Oculto', 'feedback' => 'Incorreto'],
            ['letter' => 'B', 'text' => 'Os convidados', 'feedback' => 'Correto!'],
            ['letter' => 'C', 'text' => 'Indeterminado', 'feedback' => 'Incorreto'],
            ['letter' => 'D', 'text' => 'Inexistente', 'feedback' => 'Incorreto'],
            ['letter' => 'E', 'text' => 'Chegaram', 'feedback' => 'Verbo'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $this->professor->id,
        'area' => 'Língua Portuguesa',
        'require_command_highlight' => true,
        'require_clue_highlight' => true,
        'expected_commands' => 'Identifique o sujeito',
        'expected_clues' => 'os convidados',
    ]);
});

test('updating a question from multiple choice to direct input removes existing options', function () {
    $question = Question::create([
        'user_id' => $this->professor->id,
        'area' => 'Matemática',
        'answer_type' => 'multiple_choice',
        'prompt' => 'Quanto vale $\pi$ aproximado?',
        'correct_option' => 'A',
    ]);

    QuestionOption::create(['question_id' => $question->id, 'letter' => 'A', 'text' => '3,14']);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'B', 'text' => '2,71']);

    $this->assertDatabaseCount('question_options', 2);

    $response = $this->actingAs($this->professor)->put(route('professor.perguntas.update', $question), [
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Qual o valor de $\pi$ aproximado a duas casas decimais?',
        'expected_answer' => '3.14',
        'expected_answer_explanation' => 'Pi é aproximadamente 3,14159...',
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question->refresh();
    expect($question->answer_type)->toBe('direct_input')
        ->and($question->expected_answer)->toBe('3.14')
        ->and($question->correct_option)->toBeNull();

    $this->assertDatabaseCount('question_options', 0);
});

test('professor can create a math question with expected answer photo and formulas in alternatives', function () {
    $fakeExpectedImage = UploadedFile::fake()->image('gabarito_resolucao.png', 800, 600);

    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'answer_type' => 'multiple_choice',
        'prompt' => 'Qual é a fórmula de Bhaskara para encontrar as raízes?',
        'expected_answer_explanation' => 'A fórmula geral utiliza o discriminante delta...',
        'expected_answer_image' => $fakeExpectedImage,
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'x = \frac{-b \pm \sqrt{\Delta}}{2a}', 'feedback' => 'Correto!'],
            ['letter' => 'B', 'text' => 'x = \frac{b \pm \sqrt{\Delta}}{2a}', 'feedback' => 'Incorreto: esqueceu o sinal negativo de b'],
            ['letter' => 'C', 'text' => 'x = \frac{-b \pm \Delta}{2a}', 'feedback' => 'Incorreto: delta precisa estar na raiz'],
            ['letter' => 'D', 'text' => 'x = \frac{-b \pm \sqrt{\Delta}}{a}', 'feedback' => 'Incorreto: denominador é 2a'],
            ['letter' => 'E', 'text' => 'x = -b \pm \sqrt{\Delta}', 'feedback' => 'Incorreto: falta dividir por 2a'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'like', '%Bhaskara%')->first();
    expect($question)->not->toBeNull()
        ->and($question->hasExpectedAnswerImage())->toBeTrue();

    Storage::disk('local')->assertExists($question->expected_answer_image_path);

    // Verifica que as alternativas guardaram as fórmulas complexas
    $optionA = $question->options()->where('letter', 'A')->first();
    expect($optionA->text)->toBe('x = \frac{-b \pm \sqrt{\Delta}}{2a}');

    // Verifica se a rota de servir a imagem da resolução esperada responde 200
    $imgResponse = $this->actingAs($this->professor)
        ->get(route('midia.questao.resolucao_esperada', $question));

    $imgResponse->assertOk();
});

test('professor can remove expected answer photo upon updating question', function () {
    $path = UploadedFile::fake()->image('old_gabarito.jpg')->store('questions/expected_answers', 'local');

    $question = Question::create([
        'user_id' => $this->professor->id,
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Calcule $\int x dx$',
        'expected_answer' => '\frac{x^2}{2} + C',
        'expected_answer_image_path' => $path,
    ]);

    Storage::disk('local')->assertExists($path);

    $response = $this->actingAs($this->professor)->put(route('professor.perguntas.update', $question), [
        'area' => 'Matemática',
        'answer_type' => 'direct_input',
        'prompt' => 'Calcule $\int x dx$',
        'expected_answer' => '\frac{x^2}{2} + C',
        'remove_expected_answer_image' => '1',
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question->refresh();
    expect($question->expected_answer_image_path)->toBeNull()
        ->and($question->hasExpectedAnswerImage())->toBeFalse();

    Storage::disk('local')->assertMissing($path);
});
