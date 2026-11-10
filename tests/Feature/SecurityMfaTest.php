<?php

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('usuário ativa autenticação TOTP após fornecer código válido gerado', function () {
    $service = new TotpService;
    $secret = $service->generateSecret();

    $user = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'totp_secret' => null,
    ]);

    $validCode = $service->calculateCode($secret, (int) floor(time() / 30));

    $response = $this->actingAs($user)
        ->withSession(['totp_pending_secret' => $secret])
        ->post(route('security.totp.enable'), [
            'code' => $validCode,
        ]);

    $response->assertRedirect(route('security.index'));

    $user->refresh();
    expect($user->isTotpEnabled())->toBeTrue();
});

test('login com TOTP ativo solicita o código de 6 dígitos', function () {
    $service = new TotpService;
    $secret = $service->generateSecret();

    $user = User::factory()->create([
        'email' => 'prof.totp@sesi.org.br',
        'password' => Hash::make('MinhaSenhaProfessor2026!Forte'),
        'role' => 'professor',
        'status' => 'active',
        'totp_secret' => $secret,
        'totp_enabled_at' => now(),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'prof.totp@sesi.org.br',
        'password' => 'MinhaSenhaProfessor2026!Forte',
    ]);

    $response->assertRedirect(route('login.totp'));
    expect(auth()->check())->toBeFalse();

    $validCode = $service->calculateCode($secret, (int) floor(time() / 30));

    $verifyResponse = $this->withSession(['totp_auth_user_id' => $user->id])
        ->post(route('login.totp'), [
            'code' => $validCode,
        ]);

    $verifyResponse->assertRedirect(route('professor.salas.index'));
    expect(auth()->id())->toBe($user->id);
});

test('super-usuário pode desabilitar o TOTP de um professor que perdeu o autenticador', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    $professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'totp_secret' => 'SECRETBASE32ABC',
        'totp_enabled_at' => now(),
    ]);

    expect($professor->isTotpEnabled())->toBeTrue();

    $response = $this->actingAs($admin)
        ->post(route('admin.professores.disable_totp', $professor));

    $response->assertRedirect();

    $professor->refresh();
    expect($professor->isTotpEnabled())->toBeFalse();
});
