<?php

use App\Events\StudentAttemptUpdated;
use App\Events\StudentJoinedClassroom;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('dispara evento StudentJoinedClassroom ao estudante entrar na sala de aula', function () {
    Event::fake([StudentJoinedClassroom::class]);

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Realtime',
    ]);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 1',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Reverb 1',
        'code' => 'REVERB01',
        'is_active' => true,
    ]);

    $response = $this->post(route('estudante.iniciar'), [
        'code' => 'REVERB01',
        'student_name' => 'Maria Silva',
    ]);

    $response->assertRedirect();

    Event::assertDispatched(StudentJoinedClassroom::class, function ($event) use ($classroom) {
        $channels = $event->broadcastOn();

        return $event->attempt->student_name === 'Maria Silva'
            && count($channels) === 1
            && $channels[0]->name === 'classroom.'.$classroom->id;
    });
});

test('dispara evento StudentAttemptUpdated ao salvar resposta do estudante', function () {
    Event::fake([StudentAttemptUpdated::class]);

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Simulado de Realtime',
    ]);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão 1',
        'support_type' => 'text',
        'correct_option' => 'B',
        'require_command_highlight' => false,
        'require_clue_highlight' => false,
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma Reverb 2',
        'code' => 'REVERB02',
        'is_active' => true,
    ]);

    // Entra na sala para gerar a sessão
    $this->post(route('estudante.iniciar'), [
        'code' => 'REVERB02',
        'student_name' => 'João Souza',
    ]);

    $response = $this->post(route('estudante.salvar_resposta', ['code' => 'REVERB02']), [
        'question_id' => $question->id,
        'answer' => 'B',
        'reason' => 'Tenho certeza que é a B',
        'markers' => [],
    ]);

    $response->assertJson(['success' => true]);

    Event::assertDispatched(StudentAttemptUpdated::class, function ($event) use ($classroom) {
        $channels = $event->broadcastOn();

        return $event->attempt->student_name === 'João Souza'
            && count($channels) === 1
            && $channels[0]->name === 'classroom.'.$classroom->id;
    });
});
