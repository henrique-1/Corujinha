<?php

use App\Models\TeachingArea;
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

test('super-usuário pode remover o convite de um professor pendente', function () {
    $admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $professor = User::create([
        'name' => 'Professor Cancelado',
        'email' => 'cancelado@sesi.org.br',
        'role' => 'professor',
        'status' => 'pending',
        'invitation_token' => 'token-a-remover',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.professores.destroy_invite', $professor));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(User::where('email', 'cancelado@sesi.org.br')->exists())->toBeFalse();
});

test('super-usuário não pode remover convite de um professor que já ativou a conta', function () {
    $admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'email' => 'ativo@sesi.org.br',
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.professores.destroy_invite', $professor));

    $response->assertSessionHas('error');
    expect(User::where('email', 'ativo@sesi.org.br')->exists())->toBeTrue();
});

test('super-usuário não visualiza o CPF dos professores na lista de professores', function () {
    $admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'cpf' => '52998224725',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.professores.index'));

    $response->assertOk();
    $response->assertDontSee('529.982.247-25');
    $response->assertDontSee('52998224725');
    $response->assertDontSee('<th>CPF</th>', false);
});

test('professor finaliza cadastro selecionando disciplinas sugeridas e adicionando nova área', function () {
    $professor = User::create([
        'name' => 'Marcos Vinicius',
        'email' => 'marcos.vinicius@sesi.org.br',
        'role' => 'professor',
        'status' => 'pending',
        'invitation_token' => 'token-areas-12345',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->post(route('invitation.accept', ['token' => 'token-areas-12345']), [
        'cpf' => '529.982.247-25',
        'teaching_areas' => ['Matemática', 'Física', 'Robótica Avançada'],
        'password' => 'MinhaSenhaProfessor2026!Forte',
        'password_confirmation' => 'MinhaSenhaProfessor2026!Forte',
    ]);

    $response->assertRedirect(route('security.passkey.suggestion'));

    $professor->refresh();

    expect($professor->status)->toBe('active')
        ->and($professor->teachingAreas)->toHaveCount(3)
        ->and($professor->teachingAreas->pluck('name')->all())->toContain('Matemática', 'Física', 'Robótica Avançada');
});

test('super-usuário visualiza a coluna de área de atuação na lista de professores', function () {
    $admin = User::factory()->create([
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $profComArea = User::factory()->create([
        'name' => 'Professora de História',
        'email' => 'historia@exemplo.com',
        'role' => 'professor',
        'status' => 'active',
    ]);
    $areaHistoria = TeachingArea::firstOrCreate(['name' => 'História']);
    $areaFilosofia = TeachingArea::firstOrCreate(['name' => 'Filosofia']);
    $profComArea->teachingAreas()->attach([$areaHistoria->id, $areaFilosofia->id]);

    $profSemArea = User::factory()->create([
        'name' => 'Professor Sem Disciplina',
        'email' => 'sem.disciplina@exemplo.com',
        'role' => 'professor',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.professores.index'));

    $response->assertOk();
    $response->assertSee('Área de Atuação');
    $response->assertSee('Professora de História');
    $response->assertSee('História');
    $response->assertSee('Filosofia');
    $response->assertSee('Professor Sem Disciplina');
});

test('professor pode informar sua instituicao de atuacao ao completar o cadastro', function () {
    $professor = User::create([
        'name' => 'Professor Ativacao Instituicao',
        'email' => 'ativacao.inst@exemplo.com',
        'role' => 'professor',
        'status' => 'pending',
        'invitation_token' => 'token-com-instituicao',
        'invitation_sent_at' => now(),
    ]);

    $viewResponse = $this->get(route('invitation.accept', 'token-com-instituicao'));
    $viewResponse->assertOk()
        ->assertSee('Instituição onde Atua')
        ->assertDontSee('Unidade SESI');

    $response = $this->post(route('invitation.accept', 'token-com-instituicao'), [
        'cpf' => '52998224725',
        'institution' => 'Colégio de Aplicação Experimental',
        'password' => 'MinhaSenhaForte2026!Com15Chars',
        'password_confirmation' => 'MinhaSenhaForte2026!Com15Chars',
    ]);

    $response->assertRedirect(route('security.passkey.suggestion'));

    $professor->refresh();
    expect($professor->status)->toBe('active')
        ->and($professor->institution)->toBe('Colégio de Aplicação Experimental');
});
