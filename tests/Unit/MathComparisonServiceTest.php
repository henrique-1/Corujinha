<?php

use App\Services\MathComparisonService;

test('compara igualdade de inteiros e decimais com virgula ou ponto', function () {
    $service = new MathComparisonService;

    expect($service->isEquivalent('15', '15'))->toBeTrue()
        ->and($service->isEquivalent('3,5', '3.5'))->toBeTrue()
        ->and($service->isEquivalent('3.50', '3,5'))->toBeTrue()
        ->and($service->isEquivalent(' 42 ', '42'))->toBeTrue()
        ->and($service->isEquivalent('10', '11'))->toBeFalse();
});

test('compara igualdade de fracoes e LaTeX', function () {
    $service = new MathComparisonService;

    expect($service->isEquivalent('\frac{1}{2}', '1/2'))->toBeTrue()
        ->and($service->isEquivalent('\frac{1}{2}', '0.5'))->toBeTrue()
        ->and($service->isEquivalent('$\frac{3}{4}$', '3/4'))->toBeTrue()
        ->and($service->isEquivalent('0,75', '\frac{3}{4}'))->toBeTrue()
        ->and($service->isEquivalent('\frac{2}{4}', '0.5'))->toBeTrue();
});

test('compara igualdade de expressoes com variaveis e espacos', function () {
    $service = new MathComparisonService;

    expect($service->isEquivalent('x = 5', '5'))->toBeTrue()
        ->and($service->isEquivalent('X = 5', 'x=5'))->toBeTrue()
        ->and($service->isEquivalent('2x + 1', '2x+1'))->toBeTrue()
        ->and($service->isEquivalent('$y = \frac{x}{2}$', 'y = x / 2'))->toBeTrue();
});
