<?php

use App\Models\GradeLevel;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('telas de criacao e edicao exibem descritor com foco pedagogico e sugestoes de turmas', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    // Verifica tela de criação
    $responseCreate = $this->get(route('professor.perguntas.create'));
    $responseCreate->assertOk();
    $responseCreate->assertSee('Descritor / Foco Pedagógico / Habilidade');
    $responseCreate->assertSee('4º Ano');
    $responseCreate->assertSee('6º Ano');
    $responseCreate->assertSee('3º Ano EM');

    // Cria uma pergunta para verificar edição
    $question = Question::create([
        'user_id' => $professor->id,
        'area' => 'Matemática',
        'grade_level' => '7º Ano',
        'time_limit_seconds' => 180,
        'focus' => 'D04 - Relações Geométricas',
        'prompt' => 'Qual é o perímetro da figura?',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    $responseEdit = $this->get(route('professor.perguntas.edit', $question));
    $responseEdit->assertOk();
    $responseEdit->assertSee('Descritor / Foco Pedagógico / Habilidade');
    $responseEdit->assertSee('4º Ano');
    $responseEdit->assertSee('3º Ano EM');
});

test('unifica variacoes de ensino medio para o valor canonico cadastrado', function (string $inputVariation, string $expectedCanonical) {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $response = $this->post(route('professor.perguntas.store'), [
        'area' => 'Língua Portuguesa',
        'grade_level' => $inputVariation,
        'time_limit_seconds' => 180,
        'focus' => 'Descritor D12 - Finalidade do texto',
        'prompt' => 'Qual a função comunicativa do texto apresentado?',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'Alternativa A', 'feedback' => 'Correto'],
            ['letter' => 'B', 'text' => 'Alternativa B', 'feedback' => 'Incorreto'],
            ['letter' => 'C', 'text' => 'Alternativa C', 'feedback' => 'Incorreto'],
            ['letter' => 'D', 'text' => 'Alternativa D', 'feedback' => 'Incorreto'],
            ['letter' => 'E', 'text' => 'Alternativa E', 'feedback' => 'Incorreto'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $this->assertDatabaseHas('questions', [
        'user_id' => $professor->id,
        'grade_level' => $expectedCanonical,
        'focus' => 'Descritor D12 - Finalidade do texto',
    ]);
})->with([
    ['3º Ano EM', '3º Ano EM'],
    ['3 EM', '3º Ano EM'],
    ['3º ano em', '3º Ano EM'],
    ['3 ano em', '3º Ano EM'],
    ['6 º Ano', '6º Ano'],
    ['6 ano', '6º Ano'],
    ['6º ano', '6º Ano'],
]);

test('cadastra nova turma que nao esta nas sugestoes e combina repeticoes semelhantes', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    // Primeiro cadastro com turma nova personalizada
    $this->post(route('professor.perguntas.store'), [
        'area' => 'História',
        'grade_level' => 'EJA',
        'time_limit_seconds' => 180,
        'prompt' => 'Questão 1 EJA',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'A'],
            ['letter' => 'B', 'text' => 'B'],
            ['letter' => 'C', 'text' => 'C'],
            ['letter' => 'D', 'text' => 'D'],
            ['letter' => 'E', 'text' => 'E'],
        ],
    ]);

    $this->assertDatabaseHas('grade_levels', [
        'name' => 'EJA',
        'normalized_name' => 'eja',
        'is_default' => false,
    ]);

    $this->assertDatabaseHas('questions', [
        'prompt' => 'Questão 1 EJA',
        'grade_level' => 'EJA',
    ]);

    // Segundo cadastro com variação da mesma turma nova (ex: "eja" minúsculo)
    $this->post(route('professor.perguntas.store'), [
        'area' => 'Geografia',
        'grade_level' => 'eja',
        'time_limit_seconds' => 180,
        'prompt' => 'Questão 2 EJA',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'A'],
            ['letter' => 'B', 'text' => 'B'],
            ['letter' => 'C', 'text' => 'C'],
            ['letter' => 'D', 'text' => 'D'],
            ['letter' => 'E', 'text' => 'E'],
        ],
    ]);

    // Verifica que não duplicou o registro no banco de turmas
    expect(GradeLevel::where('normalized_name', 'eja')->count())->toBe(1);

    // E a pergunta ficou com o nome canônico 'EJA'
    $this->assertDatabaseHas('questions', [
        'prompt' => 'Questão 2 EJA',
        'grade_level' => 'EJA',
    ]);
});

test('edicao de pergunta atualiza e unifica turma escolar', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $question = Question::create([
        'user_id' => $professor->id,
        'area' => 'Física',
        'grade_level' => '1º Ano EM',
        'time_limit_seconds' => 180,
        'prompt' => 'Velocidade média',
        'support_type' => 'text',
        'correct_option' => 'B',
    ]);

    $this->put(route('professor.perguntas.update', $question), [
        'area' => 'Física',
        'grade_level' => '2 EM',
        'time_limit_seconds' => 180,
        'prompt' => 'Velocidade média atualizada',
        'correct_option' => 'B',
        'options' => [
            ['letter' => 'A', 'text' => 'A'],
            ['letter' => 'B', 'text' => 'B'],
            ['letter' => 'C', 'text' => 'C'],
            ['letter' => 'D', 'text' => 'D'],
            ['letter' => 'E', 'text' => 'E'],
        ],
    ]);

    expect($question->fresh()->grade_level)->toBe('2º Ano EM');
});

test('professor pode cadastrar multiplos termos em descritor foco e habilidade', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $terms = ['D04 - Relações Geométricas', 'Perímetro e Área', 'Habilidade EF07MA29'];

    $response = $this->post(route('professor.perguntas.store'), [
        'area' => 'Matemática',
        'grade_level' => '7º Ano',
        'time_limit_seconds' => 180,
        'focus_terms' => $terms,
        'prompt' => 'Calcule o perímetro do triângulo.',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'Opção A'],
            ['letter' => 'B', 'text' => 'Opção B'],
            ['letter' => 'C', 'text' => 'Opção C'],
            ['letter' => 'D', 'text' => 'Opção D'],
            ['letter' => 'E', 'text' => 'Opção E'],
        ],
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'Calcule o perímetro do triângulo.')->first();
    expect($question)->not->toBeNull()
        ->and($question->focus_terms)->toEqual($terms)
        ->and($question->focus)->toBe('D04 - Relações Geométricas, Perímetro e Área, Habilidade EF07MA29');

    // Verifica que a tela de edição exibe os blocos
    $responseEdit = $this->get(route('professor.perguntas.edit', $question));
    $responseEdit->assertOk();
    $responseEdit->assertSee('D04 - Relações Geométricas');
    $responseEdit->assertSee('Perímetro e Área');
    $responseEdit->assertSee('Habilidade EF07MA29');
});

test('professor pode atualizar multiplos termos de foco na edicao da pergunta', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $this->actingAs($professor);

    $question = Question::create([
        'user_id' => $professor->id,
        'area' => 'Língua Portuguesa',
        'grade_level' => '9º Ano',
        'time_limit_seconds' => 180,
        'focus' => 'D01',
        'focus_terms' => ['D01'],
        'prompt' => 'Compreensão de Texto',
        'correct_option' => 'A',
    ]);

    $newTerms = ['Descritor D04', 'Efeito de Sentido', 'EF09LP04'];

    $this->put(route('professor.perguntas.update', $question), [
        'area' => 'Língua Portuguesa',
        'grade_level' => '9º Ano',
        'time_limit_seconds' => 180,
        'focus_terms' => $newTerms,
        'prompt' => 'Compreensão de Texto Atualizada',
        'correct_option' => 'A',
        'options' => [
            ['letter' => 'A', 'text' => 'Opção A'],
            ['letter' => 'B', 'text' => 'Opção B'],
            ['letter' => 'C', 'text' => 'Opção C'],
            ['letter' => 'D', 'text' => 'Opção D'],
            ['letter' => 'E', 'text' => 'Opção E'],
        ],
    ]);

    $fresh = $question->fresh();
    expect($fresh->focus_terms)->toEqual($newTerms)
        ->and($fresh->focus)->toBe('Descritor D04, Efeito de Sentido, EF09LP04');
});
