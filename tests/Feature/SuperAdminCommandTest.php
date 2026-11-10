<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cria um super-usuário com sucesso via comando artisan com dados válidos', function () {
    // CPF matematicamente válido: 52998224725
    $this->artisan('app:create-super-admin', [
        '--name' => 'Super Administrador SESI',
        '--email' => 'super.admin@sesi.org.br',
        '--cpf' => '529.982.247-25',
        '--password' => 'MinhaSenhaSeguraComMaisDe15Caracteres!',
    ])
        ->assertSuccessful();

    $user = User::where('email', 'super.admin@sesi.org.br')->first();

    expect($user)->not->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue()
        ->and($user->isActive())->toBeTrue()
        ->and($user->cpf)->toBe('52998224725');
});

test('rejeita criação com senha com menos de 15 caracteres (NIST SP 800-63B-4)', function () {
    $this->artisan('app:create-super-admin', [
        '--name' => 'Admin Invalido',
        '--email' => 'admin.invalido@sesi.org.br',
        '--cpf' => '529.982.247-25',
        '--password' => 'SenhaCurta123', // 13 chars (< 15)
    ])
        ->assertFailed();

    expect(User::where('email', 'admin.invalido@sesi.org.br')->exists())->toBeFalse();
});

test('rejeita criação com CPF matematicamente inválido', function () {
    $this->artisan('app:create-super-admin', [
        '--name' => 'Admin CPF Invalido',
        '--email' => 'admin.cpf@sesi.org.br',
        '--cpf' => '123.456.789-00', // CPF falso com DV incorreto
        '--password' => 'MinhaSenhaSeguraComMaisDe15Caracteres!',
    ])
        ->assertFailed();

    expect(User::where('email', 'admin.cpf@sesi.org.br')->exists())->toBeFalse();
});
