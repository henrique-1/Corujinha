<?php

use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);
});

test('seleciona automaticamente N questoes com base em area, turma e foco', function () {
    // Cria 5 questões de Matemática 9º Ano com foco D1
    for ($i = 1; $i <= 5; $i++) {
        $q = Question::create([
            'user_id' => $this->professor->id,
            'prompt' => "Pergunta Matemática $i",
            'area' => 'Matemática',
            'grade_level' => '9º Ano',
            'focus' => 'D1 - Teorema de Pitágoras',
            'correct_option' => 'A',
        ]);
        foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
            QuestionOption::create([
                'question_id' => $q->id,
                'letter' => $letter,
                'text' => "Opção $letter",
            ]);
        }
    }

    // Cria 3 questões de História (não devem ser selecionadas)
    for ($i = 1; $i <= 3; $i++) {
        $q = Question::create([
            'user_id' => $this->professor->id,
            'prompt' => "Pergunta História $i",
            'area' => 'História',
            'grade_level' => '9º Ano',
            'focus' => 'Revolução Francesa',
            'correct_option' => 'A',
        ]);
        foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
            QuestionOption::create([
                'question_id' => $q->id,
                'letter' => $letter,
                'text' => "Opção $letter",
            ]);
        }
    }

    $response = $this->actingAs($this->professor)->postJson(route('professor.avaliacoes.auto_select'), [
        'area' => 'Matemática',
        'grade_level' => '9º Ano',
        'focus' => 'Pitágoras',
        'quantity' => 3,
        'mode' => 'random',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'total_matching' => 5,
            'selected_count' => 3,
            'requested_count' => 3,
        ]);

    $json = $response->json();
    expect($json['question_uuids'])->toHaveCount(3);
});

test('retorna todas as questoes disponiveis quando N for maior que o total compativel', function () {
    for ($i = 1; $i <= 2; $i++) {
        $q = Question::create([
            'user_id' => $this->professor->id,
            'prompt' => "Pergunta Física $i",
            'area' => 'Física',
            'grade_level' => '1ª Série EM',
            'focus' => 'Cinemática',
            'correct_option' => 'A',
        ]);
        foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
            QuestionOption::create([
                'question_id' => $q->id,
                'letter' => $letter,
                'text' => "Opção $letter",
            ]);
        }
    }

    $response = $this->actingAs($this->professor)->postJson(route('professor.avaliacoes.auto_select'), [
        'area' => 'Física',
        'grade_level' => '1ª Série EM',
        'quantity' => 10,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'total_matching' => 2,
            'selected_count' => 2,
            'requested_count' => 10,
        ]);
});

test('inclui questoes de pastas compartilhadas com o professor', function () {
    $colega = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'email' => 'colega.compartilhou@sesi.org.br',
    ]);

    $folder = QuestionFolder::create([
        'user_id' => $colega->id,
        'name' => 'Biologia Geral',
        'visibility' => 'shared',
    ]);

    $folder->shares()->create([
        'user_id' => $this->professor->id,
        'email' => $this->professor->email,
        'permission' => 'view',
    ]);

    $q = Question::create([
        'user_id' => $colega->id,
        'folder_id' => $folder->id,
        'prompt' => 'O que é mitose?',
        'area' => 'Biologia',
        'grade_level' => '2ª Série EM',
        'focus' => 'Divisão Celular',
        'correct_option' => 'C',
    ]);
    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
        QuestionOption::create([
            'question_id' => $q->id,
            'letter' => $letter,
            'text' => "Opção $letter",
        ]);
    }

    $response = $this->actingAs($this->professor)->postJson(route('professor.avaliacoes.auto_select'), [
        'area' => 'Biologia',
        'grade_level' => '2ª Série EM',
        'quantity' => 5,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'selected_count' => 1,
        ]);

    expect($response->json('question_uuids.0'))->toBe($q->uuid);
});
