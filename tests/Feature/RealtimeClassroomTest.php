<?php

use App\Events\QuestionUpdated;
use App\Events\StudentAttemptUpdated;
use App\Events\StudentJoinedClassroom;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
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

    Event::assertDispatched(StudentJoinedClassroom::class, function ($event) use ($classroom, $professor) {
        $channels = $event->broadcastOn();
        $channelNames = array_map(fn ($c) => $c->name, $channels);

        return $event->attempt->student_name === 'Maria Silva'
            && in_array('classroom.'.$classroom->id, $channelNames, true)
            && in_array('professor.'.$professor->id, $channelNames, true);
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

test('cadastro de sala nao falha com erro 500 quando servidor reverb esta inacessivel', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.options' => [
            'host' => '127.0.0.1',
            'port' => 59999,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);
    Broadcast::purge('reverb');

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Teste',
    ]);

    $response = $this->actingAs($professor)->post(route('professor.salas.store'), [
        'name' => '8º ano A',
        'assessment_id' => $assessment->id,
        'code' => 'AVAL-ORTA',
        'collective_review_enabled' => '1',
    ]);

    $classroom = Classroom::where('code', 'AVAL-ORTA')->first();
    expect($classroom)->not->toBeNull();
    $response->assertRedirect(route('professor.salas.show', $classroom));

    $deleteResponse = $this->actingAs($professor)->delete(route('professor.salas.destroy', $classroom));
    $deleteResponse->assertRedirect(route('professor.salas.index'));
    expect(Classroom::find($classroom->id))->toBeNull();
});

test('dispara evento QuestionUpdated para as salas vinculadas ao editar uma questao', function () {
    Event::fake([QuestionUpdated::class]);

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Enunciado original',
        'support_type' => 'text',
        'support_text' => '<p>Texto original</p>',
        'correct_option' => 'A',
    ]);

    // Questão ainda sem nenhuma sala vinculada não deve disparar QuestionUpdated
    $optionsPayload = [
        ['letter' => 'A', 'text' => 'Opção A1', 'feedback' => ''],
        ['letter' => 'B', 'text' => 'Opção B1', 'feedback' => ''],
        ['letter' => 'C', 'text' => 'Opção C1', 'feedback' => ''],
        ['letter' => 'D', 'text' => 'Opção D1', 'feedback' => ''],
        ['letter' => 'E', 'text' => 'Opção E1', 'feedback' => ''],
    ];

    $this->actingAs($professor)->put(route('professor.perguntas.update', $question), [
        'prompt' => 'Enunciado sem sala',
        'support_text' => '<p>Texto sem sala</p>',
        'correct_option' => 'A',
        'options' => $optionsPayload,
    ])->assertRedirect(route('professor.perguntas.index'));

    Event::assertNotDispatched(QuestionUpdated::class);

    // Vincula a questão a uma avaliação e duas salas
    $assessment = Assessment::create([
        'user_id' => $professor->id,
        'title' => 'Avaliação Tempo Real',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1, 'points' => 2.0]);

    $classroom1 = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala 1',
        'code' => 'LIVE-001',
        'is_active' => true,
    ]);
    $classroom2 = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala 2',
        'code' => 'LIVE-002',
        'is_active' => true,
    ]);

    $updatedOptions = [
        ['letter' => 'A', 'text' => 'Nova A', 'feedback' => ''],
        ['letter' => 'B', 'text' => 'Nova B', 'feedback' => ''],
        ['letter' => 'C', 'text' => 'Nova C', 'feedback' => ''],
        ['letter' => 'D', 'text' => 'Nova D', 'feedback' => ''],
        ['letter' => 'E', 'text' => 'Nova E', 'feedback' => ''],
    ];

    $this->actingAs($professor)->put(route('professor.perguntas.update', $question), [
        'prompt' => 'Enunciado atualizado ao vivo',
        'support_title' => 'Novo Título',
        'support_text' => '<p><strong>Texto</strong> atualizado ao vivo</p>',
        'time_limit_seconds' => 240,
        'correct_option' => 'B',
        'options' => $updatedOptions,
    ])->assertRedirect(route('professor.perguntas.index'));

    Event::assertDispatched(QuestionUpdated::class, function (QuestionUpdated $event) use ($question, $classroom1, $classroom2) {
        $channels = array_map(fn ($c) => $c->name, $event->broadcastOn());
        $payload = $event->broadcastWith();

        return $event->broadcastAs() === 'question.updated'
            && in_array('classroom.'.$classroom1->id, $channels, true)
            && in_array('classroom.'.$classroom2->id, $channels, true)
            && $payload['question']['id'] === $question->id
            && $payload['question']['prompt'] === 'Enunciado atualizado ao vivo'
            && $payload['question']['support_title'] === 'Novo Título'
            && $payload['question']['support_text'] === '<p><strong>Texto</strong> atualizado ao vivo</p>'
            && $payload['question']['time_limit_seconds'] === 240
            && $payload['question']['options'][0]['text'] === 'Nova A';
    });

    // Player do estudante expõe classroomId para escutar o canal Reverb
    $this->post(route('estudante.iniciar'), [
        'code' => 'LIVE-001',
        'student_name' => 'Pedro Aluno',
    ]);

    $this->get(route('estudante.player', 'LIVE-001'))
        ->assertOk()
        ->assertSee("classroomId: {$classroom1->id}", false);
});
