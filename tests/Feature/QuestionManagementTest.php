<?php

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor cria pergunta com alternativas, devolutivas e regras de marca-texto', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $response = $this->actingAs($professor)->post(route('professor.perguntas.store'), [
        'area' => 'Língua Portuguesa',
        'focus' => 'Efeito do adjetivo',
        'support_type' => 'text',
        'support_title' => 'Tarzan é livro surpreendente',
        'support_text' => 'Tarzan mostrou ser uma grata surpresa.',
        'prompt' => 'Na manchete, o adjetivo “surpreendente” contribui para',
        'require_command_highlight' => '1',
        'require_clue_highlight' => '1',
        'expected_commands' => 'adjetivo surpreendente',
        'command_feedback' => 'Delimita a palavra a ser analisada.',
        'expected_clues' => 'grata surpresa',
        'clue_feedback' => 'Mostra o sentido de superar expectativas.',
        'correct_option' => 'B',
        'options' => [
            ['letter' => 'A', 'text' => 'Aconteceu na vida real.', 'feedback' => 'Incorreto, obra fictícia.'],
            ['letter' => 'B', 'text' => 'Expressar avaliação positiva.', 'feedback' => 'Correto, indica surpresa favorável.'],
            ['letter' => 'C', 'text' => 'Exige conhecimentos prévios.', 'feedback' => 'Incorreto, não mencionado.'],
            ['letter' => 'D', 'text' => 'Filmes são mais completos.', 'feedback' => 'Incorreto, o texto valoriza o livro.'],
            ['letter' => 'E', 'text' => 'Descreve física do personagem.', 'feedback' => 'Incorreto, adjetivo refere-se ao livro.'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'like', '%surpreendente%')->first();

    expect($question)->not->toBeNull()
        ->and($question->correct_option)->toBe('B')
        ->and($question->require_command_highlight)->toBeTrue()
        ->and($question->require_clue_highlight)->toBeTrue()
        ->and($question->options)->toHaveCount(5)
        ->and($question->options()->where('letter', 'B')->first()->feedback)->toBe('Correto, indica surpresa favorável.');
});

test('professor cria pergunta de matematica com resposta direta e formulas latex', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $response = $this->actingAs($professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'focus' => 'Frações e Equações',
        'support_type' => 'text',
        'support_title' => 'Cálculo Algébrico',
        'support_text' => '<p>Considere a equação $\\frac{x}{2} + 3 = 7$.</p>',
        'prompt' => 'Calcule o valor de $x$ na equação acima:',
        'answer_type' => 'direct_input',
        'expected_answer' => '8',
        'expected_answer_explanation' => 'Subtraindo 3 de ambos os lados: $\\frac{x}{2} = 4 \\implies x = 8$.',
        'time_limit_seconds' => 120,
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'like', '%Calcule o valor de%')->first();

    expect($question)->not->toBeNull()
        ->and($question->area)->toBe('Matemática')
        ->and($question->answer_type)->toBe('direct_input')
        ->and($question->expected_answer)->toBe('8')
        ->and($question->expected_answer_explanation)->toContain('\\frac{x}{2}')
        ->and($question->require_command_highlight)->toBeFalse()
        ->and($question->require_clue_highlight)->toBeFalse();
});

test('professor cria pergunta de matematica de multipla escolha com formulas nas alternativas', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $response = $this->actingAs($professor)->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'focus' => 'Radiciação',
        'support_type' => 'text',
        'support_title' => 'Propriedades dos Radicais',
        'support_text' => '<p>Calcule a expressão $\\sqrt{64} + \\sqrt{36}$.</p>',
        'prompt' => 'O resultado correto da expressão é:',
        'answer_type' => 'multiple_choice',
        'correct_option' => 'C',
        'options' => [
            ['letter' => 'A', 'text' => '10', 'feedback' => 'Incorreto.'],
            ['letter' => 'B', 'text' => '12', 'feedback' => 'Incorreto.'],
            ['letter' => 'C', 'text' => '14', 'feedback' => 'Correto, 8 + 6 = 14.'],
            ['letter' => 'D', 'text' => '\\sqrt{100}', 'feedback' => 'Incorreto.'],
            ['letter' => 'E', 'text' => '16', 'feedback' => 'Incorreto.'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'like', '%resultado correto%')->first();

    expect($question)->not->toBeNull()
        ->and($question->area)->toBe('Matemática')
        ->and($question->answer_type)->toBe('multiple_choice')
        ->and($question->correct_option)->toBe('C')
        ->and($question->options)->toHaveCount(5)
        ->and($question->options()->where('letter', 'D')->first()->text)->toBe('\\sqrt{100}');
});
