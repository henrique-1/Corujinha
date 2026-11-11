<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('usuário pode solicitar link de recuperação de senha e recebe notificação', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'docente@escola.sesi.br',
        'status' => 'active',
    ]);

    $response = $this->post(route('password.email'), [
        'email' => 'docente@escola.sesi.br',
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
        return ! empty($notification->token);
    });

    expect(DB::table('password_reset_tokens')->where('email', 'docente@escola.sesi.br')->exists())->toBeTrue();
});

test('usuário redefine a senha com sucesso seguindo as regras da NIST SP 800-63B-4', function () {
    $user = User::factory()->create([
        'name' => 'Roberto Carlos',
        'email' => 'roberto.carlos@escola.sesi.br',
        'status' => 'active',
        'password' => Hash::make('SenhaAntiga123456789'),
    ]);

    $token = app('auth.password.broker')->createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'roberto.carlos@escola.sesi.br',
        'password' => 'NovaFraseSegura2026!Complexa',
        'password_confirmation' => 'NovaFraseSegura2026!Complexa',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('NovaFraseSegura2026!Complexa', $user->password))->toBeTrue();
});

test('rejeita redefinição de senha com menos de 15 caracteres', function () {
    $user = User::factory()->create([
        'email' => 'prof.curta@escola.sesi.br',
        'status' => 'active',
    ]);

    $token = app('auth.password.broker')->createToken($user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'prof.curta@escola.sesi.br',
        'password' => 'Curta1234567',
        'password_confirmation' => 'Curta1234567',
    ]);

    $response->assertSessionHasErrors('password');
});

test('token de recuperação expira após 30 minutos', function () {
    $user = User::factory()->create([
        'email' => 'expirado@escola.sesi.br',
        'status' => 'active',
    ]);

    $token = app('auth.password.broker')->createToken($user);

    // Simula passagem de 31 minutos no banco
    DB::table('password_reset_tokens')
        ->where('email', 'expirado@escola.sesi.br')
        ->update(['created_at' => now()->subMinutes(31)]);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'expirado@escola.sesi.br',
        'password' => 'NovaFraseSegura2026!Valida',
        'password_confirmation' => 'NovaFraseSegura2026!Valida',
    ]);

    $response->assertSessionHasErrors('email');
});

test('endpoints de preview de email em desenvolvimento respondem com sucesso', function () {
    $convite = $this->get(route('dev.emails.convite'));
    $convite->assertOk()
        ->assertSee('Corujinha')
        ->assertSee('Finalizar Meu Cadastro');

    $reset = $this->get(route('dev.emails.reset'));
    $reset->assertOk()
        ->assertSee('Corujinha')
        ->assertSee('Redefinir Minha Senha')
        ->assertSee('expira em 30 minutos');
});
