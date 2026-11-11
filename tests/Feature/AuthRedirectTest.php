<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('usuario nao autenticado acessa a tela de login', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Acesso exclusivo para Professores e Super-Usuários');
});

test('professor autenticado que clica em login e redirecionado para o painel de salas', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $response = $this->actingAs($professor)->get(route('login'));

    $response->assertRedirect(route('professor.salas.index'));
});

test('super admin autenticado que clica em login e redirecionado para o painel de professores', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

    $response = $this->actingAs($admin)->get(route('login'));

    $response->assertRedirect(route('admin.professores.index'));
});
