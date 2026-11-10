<?php

use App\Models\User;
use App\Notifications\ProfessorInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('super-usuário pode convidar um professor informando apenas nome e email', function () {
    Notification::fake();

    $admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.professores.store'), [
        'name' => 'Professora Ana Clara',
        'email' => 'ana.clara@sesi.org.br',
    ]);

    $response->assertRedirect(route('admin.professores.index'));

    $professor = User::where('email', 'ana.clara@sesi.org.br')->first();

    expect($professor)->not->toBeNull()
        ->and($professor->role)->toBe('professor')
        ->and($professor->status)->toBe('pending')
        ->and($professor->password)->toBeNull()
        ->and($professor->cpf)->toBeNull()
        ->and($professor->invitation_token)->not->toBeNull();

    Notification::assertSentTo($professor, ProfessorInviteNotification::class);
});

test('professor finaliza o cadastro preenchendo seu CPF e senha válida segundo a NIST', function () {
    $professor = User::create([
        'name' => 'Carlos Eduardo',
        'email' => 'carlos.eduardo@sesi.org.br',
        'role' => 'professor',
        'status' => 'pending',
        'invitation_token' => 'token-seguro-12345',
        'invitation_sent_at' => now(),
    ]);

    // CPF matematicamente válido: 52998224725
    $response = $this->post(route('invitation.accept', ['token' => 'token-seguro-12345']), [
        'cpf' => '529.982.247-25',
        'password' => 'MinhaSenhaProfessor2026!Forte',
        'password_confirmation' => 'MinhaSenhaProfessor2026!Forte',
    ]);

    $response->assertRedirect(route('security.passkey.suggestion'));

    $professor->refresh();

    expect($professor->status)->toBe('active')
        ->and($professor->cpf)->toBe('52998224725')
        ->and($professor->invitation_token)->toBeNull()
        ->and($professor->password)->not->toBeNull()
        ->and(auth()->id())->toBe($professor->id);
});

test('rejeita finalização de cadastro com senha menor que 15 caracteres', function () {
    $professor = User::create([
        'name' => 'Juliana Souza',
        'email' => 'juliana.souza@sesi.org.br',
        'role' => 'professor',
        'status' => 'pending',
        'invitation_token' => 'token-teste-abc',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->from(route('invitation.accept', ['token' => 'token-teste-abc']))
        ->post(route('invitation.accept', ['token' => 'token-teste-abc']), [
            'cpf' => '529.982.247-25',
            'password' => 'Curta1234',
            'password_confirmation' => 'Curta1234',
        ]);

    $response->assertSessionHasErrors('password');

    $professor->refresh();
    expect($professor->status)->toBe('pending');
});
