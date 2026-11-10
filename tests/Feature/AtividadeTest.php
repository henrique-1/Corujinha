<?php

use App\Data\Atividade7AnoData;

test('a página da atividade interativa carrega com sucesso', function () {
    $response = $this->get(route('atividade.index'));

    $response->assertOk();
    $response->assertSee('PREPARA AVALIA');
    $response->assertSee('7º ano');
    $response->assertSee('Meu registro de leitura');
    $response->assertSee('FAIXAS COLORIDAS');
    $response->assertSee('window.__ATIVIDADE_DATA__', false);
});

test('a interface possui a modal de início obrigatório e controles do teste', function () {
    $response = $this->get(route('atividade.index'));

    $response->assertOk();
    $response->assertSee('id="start-dialog"', false);
    $response->assertSee('id="init-student"', false);
    $response->assertSee('id="init-group"', false);
    $response->assertSee('id="btn-start-test"', false);
    $response->assertSee('id="review-banner"', false);
    $response->assertSee('id="reflection-badge"', false);
    $response->assertSee('id="finish-dialog"', false);
});

test('o conjunto de dados possui 10 questões estruturadas', function () {
    $questions = Atividade7AnoData::all();

    expect($questions)->toHaveCount(10);

    foreach ($questions as $question) {
        expect($question)->toHaveKeys(['focus', 'prompt', 'options']);
        expect($question['options'])->toHaveCount(5);
    }

    // A questão 10 possui imagem de suporte
    expect($questions[9])->toHaveKey('image');
    expect($questions[9]['image'])->toBe('/images/q10_desmatar.png');
});

test('o arquivo de imagem da questão 10 existe publicamente', function () {
    expect(file_exists(public_path('images/q10_desmatar.png')))->toBeTrue();
});
