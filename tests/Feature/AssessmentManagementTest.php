<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('professor consegue excluir sua propria avaliacao quando nao ha salas vinculadas', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    $response = $this->actingAs($professor)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('success', 'Avaliação excluída com sucesso.');

    expect(Assessment::find($assessment->id))->toBeNull();
});

test('professor nao consegue excluir avaliacao vinculada a sala de aula', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma 9º Ano A',
        'code' => 'TEST99',
        'status' => 'open',
    ]);

    $response = $this->actingAs($professor)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('error');

    expect(Assessment::find($assessment->id))->not->toBeNull();
});

test('professor nao pode excluir avaliacao pertencente a outro professor', function () {
    $professor1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $professor2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor1->id]);

    $response = $this->actingAs($professor2)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertForbidden();
    expect(Assessment::find($assessment->id))->not->toBeNull();
});

test('super admin pode excluir avaliacao de qualquer professor se nao tiver salas', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);

    $response = $this->actingAs($admin)
        ->delete(route('professor.avaliacoes.destroy', $assessment));

    $response->assertRedirect(route('professor.avaliacoes.index'));
    $response->assertSessionHas('success');

    expect(Assessment::find($assessment->id))->toBeNull();
});
