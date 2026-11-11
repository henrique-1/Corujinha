<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Models\User;
use App\Services\TextHighlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function sampleOptionsPayload(): array
{
    return [
        ['letter' => 'A', 'text' => 'Alternativa A', 'feedback' => 'Feedback A'],
        ['letter' => 'B', 'text' => 'Alternativa B', 'feedback' => 'Feedback B'],
        ['letter' => 'C', 'text' => 'Alternativa C', 'feedback' => 'Feedback C'],
        ['letter' => 'D', 'text' => 'Alternativa D', 'feedback' => 'Feedback D'],
        ['letter' => 'E', 'text' => 'Alternativa E', 'feedback' => 'Feedback E'],
    ];
}

test('formularios de pergunta nao exibem datalist de area ou turma nem seletor exclusivo de tipo de apoio', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Pergunta exemplo',
        'support_type' => 'text',
        'correct_option' => 'A',
    ]);

    foreach ([route('professor.perguntas.create'), route('professor.perguntas.edit', $question)] as $url) {
        $this->actingAs($professor)
            ->get($url)
            ->assertOk()
            ->assertDontSee('area-suggestions', false)
            ->assertDontSee('grade-suggestions', false)
            ->assertDontSee('<datalist', false)
            ->assertDontSee('name="support_type"', false)
            ->assertSee('id="support-text-editor"', false)
            ->assertSee('id="support_image"', false);
    }
});

test('professor cria pergunta com titulo, texto complexo sanitizado e imagem simultaneamente', function () {
    Storage::fake('public');
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    $dirtyHtml = '<p><strong>Negrito</strong>, <em>Itálico</em>, <u>Sublinhado</u>, <s>Taxado</s> e <a href="https://corujinha.edu.br" onclick="alert(1)">Link Seguro</a><script>alert("xss")</script> <a href="javascript:evil()">Link Perigoso</a></p>';

    $response = $this->actingAs($professor)->post(route('professor.perguntas.store'), [
        'area' => 'Língua Portuguesa',
        'grade_level' => '8º Ano',
        'time_limit_seconds' => 180,
        'support_title' => 'Título Híbrido',
        'support_text' => $dirtyHtml,
        'support_image' => UploadedFile::fake()->image('apoio.png', 600, 400),
        'prompt' => 'Qual é a interpretação correta?',
        'correct_option' => 'B',
        'options' => sampleOptionsPayload(),
    ]);

    $response->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('support_title', 'Título Híbrido')->first();
    expect($question)->not->toBeNull()
        ->and($question->support_type)->toBe('mixed')
        ->and($question->hasTextSupport())->toBeTrue()
        ->and($question->hasImageSupport())->toBeTrue()
        ->and($question->support_text)->toContain('<strong>Negrito</strong>')
        ->and($question->support_text)->toContain('<em>Itálico</em>')
        ->and($question->support_text)->toContain('<u>Sublinhado</u>')
        ->and($question->support_text)->toContain('<s>Taxado</s>')
        ->and($question->support_text)->toContain('href="https://corujinha.edu.br"')
        ->and($question->support_text)->toContain('rel="noopener noreferrer"')
        ->and($question->support_text)->not->toContain('<script>')
        ->and($question->support_text)->not->toContain('onclick')
        ->and($question->support_text)->not->toContain('javascript:');

    Storage::disk('public')->assertExists($question->support_image_path);
});

test('professor pode criar pergunta sem nenhum apoio e remover imagem ao editar', function () {
    Storage::fake('public');
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);

    // Criação sem título, texto nem imagem (todos opcionais)
    $this->actingAs($professor)->post(route('professor.perguntas.store'), [
        'prompt' => 'Pergunta direta sem apoio',
        'correct_option' => 'A',
        'options' => sampleOptionsPayload(),
    ])->assertRedirect(route('professor.perguntas.index'));

    $question = Question::where('prompt', 'Pergunta direta sem apoio')->first();
    expect($question)->not->toBeNull()
        ->and($question->hasTextSupport())->toBeFalse()
        ->and($question->hasImageSupport())->toBeFalse();

    // Atualiza adicionando imagem, depois remove explicitamente
    $imagePath = UploadedFile::fake()->image('teste.jpg')->store('questions/images', 'public');
    $question->update(['support_image_path' => $imagePath, 'support_type' => 'image']);
    Storage::disk('public')->assertExists($imagePath);

    $this->actingAs($professor)->put(route('professor.perguntas.update', $question), [
        'prompt' => 'Pergunta atualizada',
        'remove_support_image' => '1',
        'correct_option' => 'A',
        'options' => sampleOptionsPayload(),
    ])->assertRedirect(route('professor.perguntas.index'));

    $question->refresh();
    expect($question->support_image_path)->toBeNull()
        ->and($question->hasImageSupport())->toBeFalse();
    Storage::disk('public')->assertMissing($imagePath);
});

test('destaques do marca-texto funcionam sobre texto complexo sem corromper tags e aparecem com imagem no resultado', function () {
    Storage::fake('public');
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $imagePath = UploadedFile::fake()->image('grafico.png')->store('questions/images', 'public');

    $html = '<p>O <strong>livro incrível</strong> aborda <u>temas atuais</u>.</p>';
    $markers = [
        ['target' => 'support', 'kind' => 'evidence', 'text' => 'livro incrível aborda', 'start' => 2, 'end' => 23],
    ];

    $highlighted = TextHighlightService::applyHighlights($html, $markers, 'support');
    expect($highlighted)->toContain('<strong><mark')
        ->and($highlighted)->toContain('livro incrível</mark></strong>')
        ->and($highlighted)->toContain('<mark')
        ->and($highlighted)->toContain('aborda</mark>')
        ->and($highlighted)->toContain('<u>temas atuais</u>');

    $question = Question::create([
        'user_id' => $professor->id,
        'support_type' => 'mixed',
        'support_title' => 'Título da Fonte',
        'support_text' => $html,
        'support_image_path' => $imagePath,
        'prompt' => 'Pergunta híbrida',
        'correct_option' => 'A',
    ]);
    QuestionOption::create(['question_id' => $question->id, 'letter' => 'A', 'text' => 'Correta']);

    $assessment = Assessment::create(['user_id' => $professor->id, 'title' => 'Prova Híbrida']);
    $assessment->questions()->attach($question->id, ['order' => 1, 'points' => 1.0]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Sala Híbrida',
        'code' => 'HIBRIDO1',
        'is_active' => true,
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Ana Souza',
        'started_at' => now(),
        'completed_at' => now(),
        'status' => 'completed',
    ]);

    StudentAnswer::create([
        'student_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'initial_answer' => 'A',
        'initial_markers' => $markers,
        'is_correct_initial' => true,
        'is_correct_final' => true,
    ]);

    $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.resultado', $classroom->code))
        ->assertOk()
        ->assertSee('Título da Fonte')
        ->assertSee('<strong><mark', false)
        ->assertSee($question->image_url, false);
});
