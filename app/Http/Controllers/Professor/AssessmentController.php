<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    /**
     * Lista as avaliações do professor.
     */
    public function index(): View
    {
        $assessments = Auth::user()->assessments()
            ->withCount(['questions', 'classrooms'])
            ->with(['classrooms' => function ($query) {
                $query->with(['studentAttempts' => function ($q) {
                    $q->select('id', 'classroom_id', 'started_at', 'completed_at');
                }])->latest();
            }])
            ->latest()
            ->paginate(10);

        return view('professor.avaliacoes.index', compact('assessments'));
    }

    /**
     * Formulário para criar uma avaliação.
     */
    public function create(): View
    {
        $questions = Auth::user()->questions()->latest()->get();
        $areas = Auth::user()->questions()->whereNotNull('area')->distinct()->pluck('area')->filter()->values();
        $gradeLevels = Auth::user()->questions()->whereNotNull('grade_level')->distinct()->pluck('grade_level')->filter()->values();

        return view('professor.avaliacoes.create', compact('questions', 'areas', 'gradeLevels'));
    }

    /**
     * Salva a avaliação e associa as perguntas selecionadas com ordem e pontuação.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_period' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required'],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0.01', 'max:99.99', 'decimal:0,2'],
        ], [
            'title.required' => 'Informe o título da avaliação.',
            'question_ids.required' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.min' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'points.*.numeric' => 'A pontuação de cada questão deve ser um valor numérico válido.',
            'points.*.min' => 'A pontuação mínima de cada questão é 0,01.',
            'points.*.max' => 'A pontuação máxima de cada questão é 99,99.',
            'points.*.decimal' => 'A pontuação deve ter no máximo 2 casas decimais.',
        ]);

        $syncData = $this->resolveQuestionSyncData($validated['question_ids'], $request->input('points', []));

        $assessment = Assessment::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'academic_period' => $validated['academic_period'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $assessment->questions()->sync($syncData);

        return redirect()->route('professor.avaliacoes.index')
            ->with('success', 'Avaliação criada com sucesso!');
    }

    /**
     * Formulário de edição de avaliação.
     */
    public function edit(Assessment $avaliacao): View
    {
        $this->authorizeAssessment($avaliacao);
        $questions = Auth::user()->questions()->latest()->get();
        $selectedQuestionIds = $avaliacao->questions->pluck('uuid')->all();
        $existingPoints = $avaliacao->questions->pluck('pivot.points', 'uuid')->all();
        $areas = Auth::user()->questions()->whereNotNull('area')->distinct()->pluck('area')->filter()->values();
        $gradeLevels = Auth::user()->questions()->whereNotNull('grade_level')->distinct()->pluck('grade_level')->filter()->values();

        return view('professor.avaliacoes.edit', [
            'assessment' => $avaliacao,
            'questions' => $questions,
            'selectedQuestionIds' => $selectedQuestionIds,
            'existingPoints' => $existingPoints,
            'areas' => $areas,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    /**
     * Atualiza a avaliação.
     */
    public function update(Request $request, Assessment $avaliacao): RedirectResponse
    {
        $this->authorizeAssessment($avaliacao);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_period' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required'],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0.01', 'max:99.99', 'decimal:0,2'],
        ], [
            'title.required' => 'Informe o título da avaliação.',
            'question_ids.required' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.min' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'points.*.numeric' => 'A pontuação de cada questão deve ser um valor numérico válido.',
            'points.*.min' => 'A pontuação mínima de cada questão é 0,01.',
            'points.*.max' => 'A pontuação máxima de cada questão é 99,99.',
            'points.*.decimal' => 'A pontuação deve ter no máximo 2 casas decimais.',
        ]);

        $syncData = $this->resolveQuestionSyncData($validated['question_ids'], $request->input('points', []));

        $avaliacao->update([
            'title' => $validated['title'],
            'academic_period' => $validated['academic_period'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $avaliacao->questions()->sync($syncData);

        return redirect()->route('professor.avaliacoes.index')
            ->with('success', 'Avaliação atualizada com sucesso!');
    }

    /**
     * Exclui uma avaliação.
     */
    public function destroy(Assessment $avaliacao): RedirectResponse
    {
        $this->authorizeAssessment($avaliacao);

        if ($avaliacao->classrooms()->exists()) {
            return redirect()->route('professor.avaliacoes.index')
                ->with('error', 'Esta avaliação não pode ser excluída pois está vinculada a uma ou mais salas de aula.');
        }

        $avaliacao->delete();

        return redirect()->route('professor.avaliacoes.index')
            ->with('success', 'Avaliação excluída com sucesso.');
    }

    /**
     * Exibe gráficos comparativos de acertos por sala e questões mais acertadas/erradas.
     */
    public function statistics(Assessment $avaliacao): View
    {
        $this->authorizeAssessment($avaliacao);

        $avaliacao->load([
            'questions',
            'classrooms.studentAttempts.answers',
        ]);

        $questions = $avaliacao->questions;
        $allowedQuestionIds = $questions->pluck('id')->all();
        $totalQuestions = $questions->count();

        // 1. Acertos por Sala de Aula (restrito às questões da avaliação e deduplicado por question_id)
        $classroomStats = $avaliacao->classrooms->map(function ($classroom) use ($totalQuestions, $allowedQuestionIds) {
            $attempts = $classroom->studentAttempts;
            $attemptsCount = $attempts->count();

            $totalCorrect = $attempts->sum(function ($attempt) use ($allowedQuestionIds) {
                return $attempt->answers
                    ->whereIn('question_id', $allowedQuestionIds)
                    ->sortByDesc('id')
                    ->unique('question_id')
                    ->filter(function ($ans) use ($attempt) {
                        return ($attempt->status === 'reviewed' || $ans->reviewed_answer !== null)
                            ? (bool) $ans->is_correct_final
                            : (bool) $ans->is_correct_initial;
                    })
                    ->count();
            });

            $possibleAnswers = $attemptsCount * $totalQuestions;
            $accuracyRate = $possibleAnswers > 0 ? round(($totalCorrect / $possibleAnswers) * 100, 1) : 0.0;
            $avgCorrect = $attemptsCount > 0 ? round($totalCorrect / $attemptsCount, 2) : 0.0;

            return [
                'id' => $classroom->uuid,
                'name' => $classroom->name,
                'code' => $classroom->code,
                'created_at' => $classroom->created_at?->format('d/m/Y') ?? '—',
                'application_date' => $classroom->applicationDateFormatted(),
                'attempts_count' => $attemptsCount,
                'total_correct' => $totalCorrect,
                'avg_correct' => $avgCorrect,
                'accuracy_rate' => $accuracyRate,
            ];
        })->sortByDesc('total_correct')->values();

        // 2. Desempenho por Questão em todas as aplicações
        $allAttempts = $avaliacao->classrooms->flatMap->studentAttempts;

        $questionStats = $questions->values()->map(function ($question, int $idx) use ($allAttempts) {
            $correctCount = 0;
            $wrongCount = 0;

            foreach ($allAttempts as $attempt) {
                $ans = $attempt->answers
                    ->where('question_id', $question->id)
                    ->sortByDesc('id')
                    ->first();
                if (! $ans || (! $ans->initial_answer && ! $ans->reviewed_answer)) {
                    continue;
                }

                $isCorrect = ($attempt->status === 'reviewed' || $ans->reviewed_answer !== null)
                    ? (bool) $ans->is_correct_final
                    : (bool) $ans->is_correct_initial;

                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $wrongCount++;
                }
            }

            $answered = $correctCount + $wrongCount;
            $accuracyRate = $answered > 0 ? round(($correctCount / $answered) * 100, 1) : 0.0;
            $errorRate = $answered > 0 ? round(($wrongCount / $answered) * 100, 1) : 0.0;

            return [
                'id' => $question->uuid,
                'number' => $idx + 1,
                'short_prompt' => mb_strimwidth($question->prompt, 0, 75, '...'),
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
                'answered_count' => $answered,
                'accuracy_rate' => $accuracyRate,
                'error_rate' => $errorRate,
            ];
        });

        $mostCorrectQuestions = $questionStats
            ->sortByDesc(fn ($item) => [$item['correct_count'], $item['accuracy_rate']])
            ->values();

        $mostWrongQuestions = $questionStats
            ->sortByDesc(fn ($item) => [$item['wrong_count'], $item['error_rate']])
            ->values();

        return view('professor.avaliacoes.estatisticas', [
            'assessment' => $avaliacao,
            'classroomStats' => $classroomStats,
            'mostCorrectQuestions' => $mostCorrectQuestions,
            'mostWrongQuestions' => $mostWrongQuestions,
            'totalApplications' => $avaliacao->classrooms->count(),
            'totalAttempts' => $allAttempts->count(),
        ]);
    }

    /**
     * Resolve e valida que todas as questões pertencem ao professor autenticado (aceitando UUID ou ID).
     *
     * @param  array<int, string|int>  $questionIdentifiers
     * @param  array<string|int, mixed>  $pointsInput
     * @return array<int, array{order: int, points: float}>
     */
    private function resolveQuestionSyncData(array $questionIdentifiers, array $pointsInput): array
    {
        /** @var Collection<int, Question> $ownedQuestions */
        $ownedQuestions = Auth::user()->questions()->get();
        $byId = $ownedQuestions->keyBy('id');
        $byUuid = $ownedQuestions->keyBy('uuid');

        $syncData = [];
        $order = 1;

        foreach ($questionIdentifiers as $rawIdentifier) {
            if (! is_string($rawIdentifier) && ! is_int($rawIdentifier)) {
                throw ValidationException::withMessages([
                    'question_ids' => 'Uma ou mais perguntas selecionadas não pertencem à sua conta.',
                ]);
            }

            $keyStr = (string) $rawIdentifier;
            $question = ctype_digit($keyStr)
                ? $byId->get((int) $keyStr)
                : $byUuid->get($keyStr);

            if (! $question) {
                throw ValidationException::withMessages([
                    'question_ids.0' => 'Uma ou mais perguntas selecionadas não pertencem à sua conta.',
                ]);
            }

            $rawPt = $pointsInput[$keyStr] ?? $pointsInput[$question->uuid] ?? $pointsInput[$question->id] ?? null;
            $pt = $rawPt !== null && is_numeric($rawPt) ? round((float) $rawPt, 2) : 1.0;

            $syncData[$question->id] = [
                'order' => $order++,
                'points' => $pt > 0 ? $pt : 1.0,
            ];
        }

        return $syncData;
    }

    private function authorizeAssessment(Assessment $assessment): void
    {
        if ($assessment->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
