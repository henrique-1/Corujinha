<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
            'description' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => [
                'required',
                Rule::exists('questions', 'id')->where('user_id', Auth::id()),
            ],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0.01', 'max:99.99', 'decimal:0,2'],
        ], [
            'title.required' => 'Informe o título da avaliação.',
            'question_ids.required' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.min' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.*.exists' => 'Uma ou mais perguntas selecionadas não pertencem à sua conta.',
            'points.*.numeric' => 'A pontuação de cada questão deve ser um valor numérico válido.',
            'points.*.min' => 'A pontuação mínima de cada questão é 0,01.',
            'points.*.max' => 'A pontuação máxima de cada questão é 99,99.',
            'points.*.decimal' => 'A pontuação deve ter no máximo 2 casas decimais.',
        ]);

        $assessment = Assessment::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $pointsInput = $request->input('points', []);
        $syncData = [];
        foreach ($validated['question_ids'] as $index => $qId) {
            $pt = isset($pointsInput[$qId]) && is_numeric($pointsInput[$qId]) ? round((float) $pointsInput[$qId], 2) : 1.0;
            $syncData[$qId] = [
                'order' => $index + 1,
                'points' => $pt > 0 ? $pt : 1.0,
            ];
        }

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
        $selectedQuestionIds = $avaliacao->questions->pluck('id')->all();
        $existingPoints = $avaliacao->questions->pluck('pivot.points', 'id')->all();
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
            'description' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => [
                'required',
                Rule::exists('questions', 'id')->where('user_id', Auth::id()),
            ],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'numeric', 'min:0.01', 'max:99.99', 'decimal:0,2'],
        ], [
            'title.required' => 'Informe o título da avaliação.',
            'question_ids.required' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.min' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.*.exists' => 'Uma ou mais perguntas selecionadas não pertencem à sua conta.',
            'points.*.numeric' => 'A pontuação de cada questão deve ser um valor numérico válido.',
            'points.*.min' => 'A pontuação mínima de cada questão é 0,01.',
            'points.*.max' => 'A pontuação máxima de cada questão é 99,99.',
            'points.*.decimal' => 'A pontuação deve ter no máximo 2 casas decimais.',
        ]);

        $avaliacao->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $pointsInput = $request->input('points', []);
        $syncData = [];
        foreach ($validated['question_ids'] as $index => $qId) {
            $pt = isset($pointsInput[$qId]) && is_numeric($pointsInput[$qId]) ? round((float) $pointsInput[$qId], 2) : 1.0;
            $syncData[$qId] = [
                'order' => $index + 1,
                'points' => $pt > 0 ? $pt : 1.0,
            ];
        }

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
        $totalQuestions = $questions->count();

        // 1. Acertos por Sala de Aula
        $classroomStats = $avaliacao->classrooms->map(function ($classroom) use ($totalQuestions) {
            $attempts = $classroom->studentAttempts;
            $attemptsCount = $attempts->count();

            $totalCorrect = $attempts->sum(function ($attempt) {
                return $attempt->answers->filter(function ($ans) use ($attempt) {
                    return ($attempt->status === 'reviewed' || $ans->reviewed_answer !== null)
                        ? (bool) $ans->is_correct_final
                        : (bool) $ans->is_correct_initial;
                })->count();
            });

            $possibleAnswers = $attemptsCount * $totalQuestions;
            $accuracyRate = $possibleAnswers > 0 ? round(($totalCorrect / $possibleAnswers) * 100, 1) : 0.0;
            $avgCorrect = $attemptsCount > 0 ? round($totalCorrect / $attemptsCount, 2) : 0.0;

            return [
                'id' => $classroom->id,
                'name' => $classroom->name,
                'code' => $classroom->code,
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
                $ans = $attempt->answers->firstWhere('question_id', $question->id);
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
                'id' => $question->id,
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

    private function authorizeAssessment(Assessment $assessment): void
    {
        if ($assessment->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
