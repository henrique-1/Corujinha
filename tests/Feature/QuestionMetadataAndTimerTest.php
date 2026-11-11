<?php

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor pode cadastrar pergunta com area, turma e tempo limite personalizado', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $response = $this->post(route('professor.perguntas.store'), [
        'area' => 'Língua Portuguesa',
        'grade_level' => '9º Ano',
        'time_limit_seconds' => 300, // 5 minutos
        'focus' => 'Interpretação Textual',
        'support_type' => 'text',
        'support_title' => 'Notícia sobre Preservação',
        'support_text' => 'Texto sobre o meio ambiente e a sustentabilidade.',
        'prompt' => 'Qual é a ideia central defendida no texto?',
        'require_command_highlight' => '1',
        'require_clue_highlight' => '1',
        'expected_commands' => 'ideia central defendida',
        'expected_clues' => 'preservação e sustentabilidade',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'Alternativa A correta', 'feedback' => 'Muito bem!'],
            ['letter' => 'B', 'text' => 'Alternativa B incorreta', 'feedback' => 'Atenção ao parágrafo 2.'],
            ['letter' => 'C', 'text' => 'Alternativa C incorreta', 'feedback' => 'Distrator.'],
            ['letter' => 'D', 'text' => 'Alternativa D incorreta', 'feedback' => 'Distrator.'],
            ['letter' => 'E', 'text' => 'Alternativa E incorreta', 'feedback' => 'Distrator.'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $professor->id,
        'area' => 'Língua Portuguesa',
        'grade_level' => '9º Ano',
        'time_limit_seconds' => 300,
        'support_title' => 'Notícia sobre Preservação',
        'correct_option' => 'A',
    ]);
});

test('filtra perguntas por area e turma no banco de questoes do professor', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    Question::create([
        'user_id' => $professor->id,
        'area' => 'Matemática',
        'grade_level' => '6º Ano',
        'time_limit_seconds' => 120,
        'prompt' => 'Calcule o valor de X',
        'support_type' => 'text',
        'correct_option' => 'C',
    ]);

    Question::create([
        'user_id' => $professor->id,
        'area' => 'Língua Portuguesa',
        'grade_level' => '9º Ano',
        'time_limit_seconds' => 180,
        'prompt' => 'Identifique o sujeito da oração',
        'support_type' => 'text',
        'correct_option' => 'B',
    ]);

    $resMat = $this->get(route('professor.perguntas.index', ['area' => 'Matemática']));
    $resMat->assertOk();
    $resMat->assertSee('Calcule o valor de X');
    $resMat->assertDontSee('Identifique o sujeito da oração');

    $res9Ano = $this->get(route('professor.perguntas.index', ['grade_level' => '9º Ano']));
    $res9Ano->assertOk();
    $res9Ano->assertSee('Identifique o sujeito da oração');
    $res9Ano->assertDontSee('Calcule o valor de X');
});
