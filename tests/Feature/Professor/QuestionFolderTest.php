<?php

use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\QuestionOption;
use App\Models\User;
use App\Notifications\FolderInviteNewUserNotification;
use App\Notifications\FolderSharedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->professor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'email' => 'dono.pasta@sesi.org.br',
    ]);
});

test('professor pode criar pasta de perguntas com visibilidade privada padrao', function () {
    $response = $this->actingAs($this->professor)->post(route('professor.pastas.store'), [
        'name' => 'Simulado 1º Bimestre',
        'description' => 'Perguntas selecionadas para a prova diagnóstica',
    ]);

    $response->assertRedirect();

    $folder = QuestionFolder::where('name', 'Simulado 1º Bimestre')->first();
    expect($folder)->not->toBeNull()
        ->and($folder->user_id)->toBe($this->professor->id)
        ->and($folder->visibility)->toBe('private');
});

test('professor pode vincular pergunta a uma pasta na criacao', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Pasta de Matemática',
        'visibility' => 'private',
    ]);

    $options = [
        ['letter' => 'A', 'text' => 'Opção A', 'feedback' => 'Fb A'],
        ['letter' => 'B', 'text' => 'Opção B', 'feedback' => 'Fb B'],
        ['letter' => 'C', 'text' => 'Opção C', 'feedback' => 'Fb C'],
        ['letter' => 'D', 'text' => 'Opção D', 'feedback' => 'Fb D'],
        ['letter' => 'E', 'text' => 'Opção E', 'feedback' => 'Fb E'],
    ];

    $response = $this->actingAs($this->professor)->post(route('professor.perguntas.store'), [
        'folder_id' => $folder->uuid,
        'prompt' => 'Quanto é 2 + 2?',
        'area' => 'Matemática',
        'grade_level' => '6º Ano',
        'time_limit_seconds' => 180,
        'correct_option' => 'A',
        'options' => $options,
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'Quanto é 2 + 2?')->first();
    expect($question)->not->toBeNull()
        ->and($question->folder_id)->toBe($folder->id);
});

test('professor compartilha pasta com colega cadastrado definindo permissao especifica', function () {
    Notification::fake();

    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Ciências da Natureza',
        'visibility' => 'private',
    ]);

    $colega = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'email' => 'colega@sesi.org.br',
    ]);

    $response = $this->actingAs($this->professor)
        ->post(route('professor.pastas.share', $folder), [
            'email' => 'colega@sesi.org.br',
            'permission' => 'edit',
        ]);

    $response->assertRedirect();
    $folder->refresh();

    expect($folder->visibility)->toBe('shared')
        ->and($folder->shares)->toHaveCount(1)
        ->and($folder->shares->first()->permission)->toBe('edit')
        ->and($folder->canEdit($colega))->toBeTrue()
        ->and($folder->canView($colega))->toBeTrue();

    Notification::assertSentTo($colega, FolderSharedNotification::class);
});

test('ao compartilhar com usuario nao cadastrado, cria convite com permissao definida', function () {
    Notification::fake();

    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Física Quântica',
        'visibility' => 'private',
    ]);

    $response = $this->actingAs($this->professor)
        ->post(route('professor.pastas.share', $folder), [
            'email' => 'novo.professor@sesi.org.br',
            'permission' => 'view',
        ]);

    $response->assertRedirect();

    $newUser = User::where('email', 'novo.professor@sesi.org.br')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->role)->toBe('professor')
        ->and($newUser->status)->toBe('pending')
        ->and($newUser->invitation_token)->not->toBeNull();

    $share = $folder->shares()->where('email', 'novo.professor@sesi.org.br')->first();
    expect($share)->not->toBeNull()
        ->and($share->permission)->toBe('view')
        ->and($share->user_id)->toBe($newUser->id);

    Notification::assertSentTo($newUser, FolderInviteNewUserNotification::class);
});

test('professor pode clonar pasta compartilhada gerando copia exata das perguntas para sua conta', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'História do Brasil',
        'description' => 'Pasta sobre o período colonial',
        'visibility' => 'shared',
    ]);

    $question = Question::create([
        'user_id' => $this->professor->id,
        'folder_id' => $folder->id,
        'prompt' => 'Em que ano ocorreu a proclamação da república?',
        'area' => 'História',
        'correct_option' => 'B',
    ]);

    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
        QuestionOption::create([
            'question_id' => $question->id,
            'letter' => $letter,
            'text' => "Alternativa $letter",
        ]);
    }

    $outroProfessor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
        'email' => 'outro@sesi.org.br',
    ]);

    $folder->shares()->create([
        'user_id' => $outroProfessor->id,
        'email' => $outroProfessor->email,
        'permission' => 'view',
    ]);

    // O outro professor clona a pasta
    $response = $this->actingAs($outroProfessor)
        ->post(route('professor.pastas.clone', $folder));

    $response->assertRedirect();

    $clonedFolder = QuestionFolder::where('user_id', $outroProfessor->id)
        ->where('name', 'Cópia de História do Brasil')
        ->first();

    expect($clonedFolder)->not->toBeNull()
        ->and($clonedFolder->visibility)->toBe('private')
        ->and($clonedFolder->description)->toBe('Pasta sobre o período colonial')
        ->and($clonedFolder->questions)->toHaveCount(1);

    $clonedQuestion = $clonedFolder->questions->first();
    expect($clonedQuestion->user_id)->toBe($outroProfessor->id)
        ->and($clonedQuestion->prompt)->toBe('Em que ano ocorreu a proclamação da república?')
        ->and($clonedQuestion->options)->toHaveCount(5)
        ->and($clonedQuestion->id)->not->toBe($question->id);
});

test('professor pode visualizar botao e modal de adicionar/mover perguntas na pasta', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Ciências Naturais',
        'visibility' => 'private',
    ]);

    $question = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'O que é a fotossíntese?',
        'area' => 'Biologia',
        'grade_level' => '7º Ano',
        'time_limit_seconds' => 180,
        'correct_option' => 'A',
    ]);

    $response = $this->actingAs($this->professor)
        ->get(route('professor.pastas.show', $folder));

    $response->assertOk();
    $response->assertSee('Adicionar / Mover Perguntas');
    $response->assertSee('O que é a fotossíntese?');
});

test('professor pode adicionar e mover perguntas do seu banco para a pasta', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Física Básica',
        'visibility' => 'private',
    ]);

    $q1 = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Questão 1 de cinemática',
        'area' => 'Física',
        'grade_level' => '1º Ano EM',
        'time_limit_seconds' => 180,
        'correct_option' => 'A',
    ]);

    $q2 = Question::create([
        'user_id' => $this->professor->id,
        'prompt' => 'Questão 2 de dinâmica',
        'area' => 'Física',
        'grade_level' => '1º Ano EM',
        'time_limit_seconds' => 180,
        'correct_option' => 'B',
    ]);

    expect($q1->folder_id)->toBeNull();
    expect($q2->folder_id)->toBeNull();

    $response = $this->actingAs($this->professor)
        ->post(route('professor.pastas.add-questions', $folder), [
            'question_uuids' => [$q1->uuid, $q2->uuid],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($q1->fresh()->folder_id)->toBe($folder->id);
    expect($q2->fresh()->folder_id)->toBe($folder->id);
});

test('professor pode remover uma pergunta da pasta retornando-a para a raiz', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Química Orgânica',
        'visibility' => 'private',
    ]);

    $question = Question::create([
        'user_id' => $this->professor->id,
        'folder_id' => $folder->id,
        'prompt' => 'Identifique a cadeia carbônica',
        'area' => 'Química',
        'grade_level' => '3º Ano EM',
        'time_limit_seconds' => 180,
        'correct_option' => 'C',
    ]);

    expect($question->folder_id)->toBe($folder->id);

    $response = $this->actingAs($this->professor)
        ->delete(route('professor.pastas.remove-question', [$folder, $question]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($question->fresh()->folder_id)->toBeNull();
});

test('professor nao pode mover perguntas de outro professor para a sua pasta', function () {
    $folder = QuestionFolder::create([
        'user_id' => $this->professor->id,
        'name' => 'Minha Pasta',
        'visibility' => 'private',
    ]);

    $outroProfessor = User::factory()->create([
        'role' => 'professor',
        'status' => 'active',
    ]);

    $perguntaDeOutro = Question::create([
        'user_id' => $outroProfessor->id,
        'prompt' => 'Pergunta do outro professor',
        'correct_option' => 'A',
    ]);

    $response = $this->actingAs($this->professor)
        ->post(route('professor.pastas.add-questions', $folder), [
            'question_uuids' => [$perguntaDeOutro->uuid],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect($perguntaDeOutro->fresh()->folder_id)->toBeNull();
});
