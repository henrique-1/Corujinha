<?php

use App\Events\ClassroomListUpdated;
use App\Events\ProfessorListUpdated;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('professor não pode criar avaliação com questões de outro professor', function () {
    $prof1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $prof2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $questionProf2 = Question::create([
        'user_id' => $prof2->id,
        'prompt' => 'Questão exclusiva do Prof 2',
        'support_type' => 'text',
        'correct_option' => 'B',
    ]);

    $this->actingAs($prof1);

    $response = $this->post(route('professor.avaliacoes.store'), [
        'title' => 'Tentativa de Avaliação Indevida',
        'question_ids' => [$questionProf2->id],
    ]);

    $response->assertSessionHasErrors('question_ids.0');
    $this->assertDatabaseMissing('assessments', [
        'title' => 'Tentativa de Avaliação Indevida',
        'user_id' => $prof1->id,
    ]);
});

test('professor não pode criar sala de aula com avaliação de outro professor', function () {
    $prof1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $prof2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessmentProf2 = Assessment::create([
        'user_id' => $prof2->id,
        'title' => 'Avaliação Exclusiva do Prof 2',
    ]);

    $this->actingAs($prof1);

    $response = $this->post(route('professor.salas.store'), [
        'name' => 'Turma Invasora',
        'assessment_id' => $assessmentProf2->id,
    ]);

    $response->assertSessionHasErrors('assessment_id');
    $this->assertDatabaseMissing('classrooms', [
        'name' => 'Turma Invasora',
        'user_id' => $prof1->id,
    ]);
});

test('professor não pode visualizar ou editar avaliação de outro professor', function () {
    $prof1 = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $prof2 = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $assessmentProf2 = Assessment::create([
        'user_id' => $prof2->id,
        'title' => 'Avaliação do Prof 2',
    ]);

    $this->actingAs($prof1);

    $this->get(route('professor.avaliacoes.edit', $assessmentProf2))
        ->assertForbidden();

    $this->put(route('professor.avaliacoes.update', $assessmentProf2), [
        'title' => 'Tentativa de alteração',
        'question_ids' => [],
    ])->assertForbidden();

    $this->delete(route('professor.avaliacoes.destroy', $assessmentProf2))
        ->assertForbidden();
});

test('dispara ProfessorListUpdated ao admin cadastrar ou remover convite de professor', function () {
    Event::fake([ProfessorListUpdated::class]);

    $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    $this->actingAs($admin);

    $response = $this->post(route('admin.professores.store'), [
        'name' => 'Professor Convidado',
        'email' => 'convidado@sesisp.org.br',
    ]);

    $response->assertRedirect(route('admin.professores.index'));

    Event::assertDispatched(ProfessorListUpdated::class, function ($event) {
        return $event->professor->email === 'convidado@sesisp.org.br'
            && $event->action === 'created';
    });
});

test('dispara ClassroomListUpdated ao professor criar nova sala de aula', function () {
    Event::fake([ClassroomListUpdated::class]);

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Teste',
    ]);

    $this->actingAs($professor);

    $response = $this->post(route('professor.salas.store'), [
        'name' => 'Turma do Reverb',
        'assessment_id' => $assessment->id,
        'code' => 'REVERB99',
    ]);

    $classroom = Classroom::where('code', 'REVERB99')->first();
    $response->assertRedirect(route('professor.salas.show', $classroom));

    Event::assertDispatched(ClassroomListUpdated::class, function ($event) use ($professor) {
        return $event->classroom->user_id === $professor->id
            && $event->action === 'created'
            && $event->broadcastOn()[0]->name === 'private-professor.'.$professor->uuid;
    });
});
