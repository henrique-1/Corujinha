<?php

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('pagina /sala possui meta robots index follow, canonico e dados estruturados para o Bing', function () {
    $response = $this->get('/sala');

    $response->assertOk()
        ->assertSee('<meta name="robots" content="index, follow', false)
        ->assertSee('<link rel="canonical" href="'.route('estudante.entrar').'">', false)
        ->assertSee('Corujinha · Sala de Avaliação e Provas Online', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:url"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('EducationalApplication', false);
});

test('pagina inicial raiz / tambem possui index follow e canonico', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('<meta name="robots" content="index, follow', false)
        ->assertSee('<link rel="canonical"', false);
});

test('pagina interna da sala de atividade mantem noindex nofollow', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $assessment = Assessment::factory()->create(['user_id' => $professor->id]);
    $question = Question::create([
        'user_id' => $professor->id,
        'prompt' => 'Questão de teste',
        'support_type' => 'none',
        'correct_option' => 'A',
    ]);
    $assessment->questions()->attach($question->id, ['order' => 1, 'points' => 10]);

    $classroom = Classroom::create([
        'user_id' => $professor->id,
        'assessment_id' => $assessment->id,
        'name' => 'Turma 9A',
        'code' => 'AVAL-9A9A9A',
        'status' => 'active',
        'starts_at' => now(),
    ]);

    $attempt = StudentAttempt::create([
        'classroom_id' => $classroom->id,
        'student_name' => 'Aluno Teste',
        'access_code' => 'TESTE123',
        'started_at' => now(),
    ]);

    $response = $this->withSession(['current_attempt_id' => $attempt->id])
        ->get(route('estudante.player', ['code' => $classroom->code]));

    $response->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('rota sitemap.xml retorna xml valido contendo /sala e /', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee('<urlset', false)
        ->assertSee('/sala</loc>', false)
        ->assertSee('<priority>1.0</priority>', false);
});

test('rota da chave indexnow retorna a chave configurada em formato texto plano', function () {
    $key = config('services.indexnow.key');

    $response = $this->get("/{$key}.txt");
    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSeeText($key);

    // Chave incorreta deve retornar 404
    $badResponse = $this->get('/chave-invalida-xyz123.txt');
    $badResponse->assertNotFound();
});

test('comando seo:bing-index submete urls ao bing e parceiros via indexnow', function () {
    Http::fake([
        'https://www.bing.com/indexnow' => Http::response(['status' => 'success'], 200),
        'https://api.indexnow.org/indexnow' => Http::response(['status' => 'success'], 202),
    ]);

    $this->artisan('seo:bing-index')
        ->assertSuccessful()
        ->expectsOutputToContain('Submetendo')
        ->expectsOutputToContain('Sucesso ao submeter para https://www.bing.com/indexnow (HTTP 200)')
        ->expectsOutputToContain('Submissão ao Bing concluída com sucesso!');
});

test('comando seo:generate-sitemap gera arquivo fisico sitemap.xml', function () {
    $this->artisan('seo:generate-sitemap')
        ->assertSuccessful()
        ->expectsOutputToContain('Arquivo sitemap.xml gerado com sucesso');

    expect(file_exists(public_path('sitemap.xml')))->toBeTrue();
    $content = file_get_contents(public_path('sitemap.xml'));
    expect($content)->toContain('<urlset')
        ->and($content)->toContain('/sala</loc>');
});
