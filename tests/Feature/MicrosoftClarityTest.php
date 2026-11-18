<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('codigo do microsoft clarity esta presente no head das paginas publicas e do aluno', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('https://www.clarity.ms/tag/', false)
        ->assertSee('zfl6cyyfh8', false);
});

test('codigo do microsoft clarity esta presente no head do painel do professor', function () {
    $professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);

    $response = $this->actingAs($professor)->get(route('professor.salas.index'));

    $response->assertOk()
        ->assertSee('https://www.clarity.ms/tag/', false)
        ->assertSee('zfl6cyyfh8', false);
});

test('componente clarity nao renderiza tag script quando id esta vazio', function () {
    config(['services.clarity.id' => '']);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertDontSee('https://www.clarity.ms/tag/', false);
});
