<?php

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor cria pergunta com alternativas, devolutivas e regras de marca-texto', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $response = $this->actingAs($professor)->post(route('professor.perguntas.store'), [
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
