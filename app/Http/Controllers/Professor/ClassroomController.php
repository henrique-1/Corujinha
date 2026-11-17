<?php

namespace App\Http\Controllers\Professor;

use App\Events\ClassroomClosed;
use App\Events\ClassroomListUpdated;
use App\Events\StudentAttemptUpdated;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    /**
     * Lista as salas de aula do professor.
     */
    public function index(): View
    {
        $classrooms = Auth::user()->classrooms()
            ->with(['assessment' => fn ($query) => $query->withCount('questions')])
            ->withCount('studentAttempts')
            ->latest()
            ->paginate(10);

        return view('professor.salas.index', compact('classrooms'));
    }

    /**
     * Formulário de criação de sala de aula.
     */
    public function create(): View
    {
        $assessments = Auth::user()->assessments()
            ->has('questions')
            ->withCount('questions')
            ->latest()
            ->get();
        $suggestedCode = Classroom::generateUniqueCode();

        return view('professor.salas.create', compact('assessments', 'suggestedCode'));
    }

    /**
     * Salva a nova sala de aula.
     */
    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('code')) && trim($request->input('code')) !== '') {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        } else {
            $request->merge(['code' => Classroom::generateUniqueCode()]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'assessment_id' => ['required'],
            'code' => ['required', 'string', 'max:20', 'unique:classrooms,code'],
            'game_mode' => ['nullable', 'string', 'in:corujinha,corujao'],
            'collective_review_enabled' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->filled('starts_at') && $request->filled('ends_at')) {
                        $start = $request->date('starts_at');
                        $end = $request->date('ends_at');
                        if ($start && $end && $end->lte($start)) {
                            $fail('A data e hora de fechamento deve ser posterior à data de abertura.');
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Informe o nome da turma ou sala.',
            'assessment_id.required' => 'Selecione uma avaliação para esta sala.',
            'code.required' => 'O código da sala é obrigatório.',
            'code.unique' => 'Este código já está em uso em outra sala.',
        ]);

        $rawAssessmentId = $validated['assessment_id'];
        if (! is_string($rawAssessmentId) && ! is_int($rawAssessmentId)) {
            return back()->withInput()->withErrors([
                'assessment_id' => 'A avaliação selecionada é inválida ou não pertence à sua conta.',
            ]);
        }

        $assessment = Assessment::withCount('questions')
            ->where('user_id', Auth::id())
            ->where(function ($query) use ($rawAssessmentId) {
                $idStr = (string) $rawAssessmentId;
                if (ctype_digit($idStr)) {
                    $query->where('id', (int) $idStr);
                } else {
                    $query->where('uuid', $idStr);
                }
            })
            ->first();

        if (! $assessment) {
            return back()->withInput()->withErrors([
                'assessment_id' => 'A avaliação selecionada é inválida ou não pertence à sua conta.',
            ]);
        }

        $gameMode = $request->input('game_mode', 'corujinha') === 'corujao' ? 'corujao' : 'corujinha';
        $reviewEnabled = $gameMode === 'corujao' ? false : $request->boolean('collective_review_enabled', true);
        $maxReviewChanges = $gameMode === 'corujao'
            ? null
            : $this->resolveValidatedMaxReviewChanges($request, (int) $assessment->questions_count, $reviewEnabled);

        $code = strtoupper(trim($validated['code']));

        $classroom = Classroom::create([
            'user_id' => Auth::id(),
            'assessment_id' => $assessment->id,
            'name' => $validated['name'],
            'code' => $code,
            'game_mode' => $gameMode,
            'collective_review_enabled' => $reviewEnabled,
            'max_review_changes' => $maxReviewChanges,
            'is_active' => true,
            'starts_at' => $request->filled('starts_at') ? $request->date('starts_at') : null,
            'ends_at' => $request->filled('ends_at') ? $request->date('ends_at') : null,
        ]);

        BroadcastService::dispatch(new ClassroomListUpdated($classroom, 'created'));

        return redirect()->route('professor.salas.show', $classroom)
            ->with('success', "Sala de Aula criada com sucesso! Código de acesso: {$classroom->code}");
    }

    /**
     * Painel e relatório da sala de aula com os resultados dos alunos.
     */
    public function show(Request $request, Classroom $sala): View
    {
        $this->authorizeClassroom($sala);

        $sala->load([
            'assessment' => fn ($query) => $query->withCount('questions'),
        ]);

        $search = trim((string) $request->input('search', ''));
        $sort = strtolower(trim((string) $request->input('sort', 'inicio')));
        $allowedSorts = ['inicio', 'nome', 'status', 'conclusao', 'acertos'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'inicio';
        }

        $defaultDirection = ($sort === 'inicio') ? 'desc' : 'asc';
        $direction = strtolower(trim((string) $request->input('direction', $defaultDirection)));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaultDirection;
        }

        $query = $sala->studentAttempts();

        if ($search !== '') {
            $query->where('student_name', 'like', "%{$search}%");
        }

        match ($sort) {
            'nome' => $query->orderBy('student_name', $direction)->orderBy('id', 'desc'),
            'status' => $query->orderBy('status', $direction)->orderBy('id', 'desc'),
            'conclusao' => $query->orderByRaw("COALESCE(reviewed_at, completed_at) {$direction}")->orderBy('id', 'desc'),
            'acertos' => $query->orderBy('score', $direction)->orderBy('id', 'desc'),
            default => $query->orderBy('started_at', $direction)->orderBy('id', $direction),
        };

        $attempts = $query->paginate(20)->withQueryString();

        $totalQuestions = (int) ($sala->assessment->questions_count ?? 0);

        return view('professor.salas.show', [
            'classroom' => $sala,
            'attempts' => $attempts,
            'totalQuestions' => $totalQuestions,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Visualização da avaliação pelo professor no mesmo formato do player do estudante.
     */
    public function previewAssessment(Classroom $sala): View
    {
        $this->authorizeClassroom($sala);

        $sala->load([
            'assessment.questions.options',
        ]);

        $questions = $sala->assessment?->questions ?? collect();

        $questionsData = $questions->map(function (Question $q, $idx) {
            return array_merge($q->toPlayerPayload(), [
                'index' => $idx + 1,
                'points' => (float) ($q->pivot->points ?? 1.0),
                'saved' => null,
            ]);
        })->values()->all();

        return view('estudante.player', [
            'classroom' => $sala,
            'attempt' => null,
            'isTeacherPreview' => true,
            'questionsData' => $questionsData,
        ]);
    }

    /**
     * Formulário de edição das configurações da sala de aula.
     */
    public function edit(Classroom $sala): View
    {
        $this->authorizeClassroom($sala);
        $sala->load(['assessment' => fn ($query) => $query->withCount('questions')]);

        return view('professor.salas.edit', [
            'classroom' => $sala,
            'totalQuestions' => (int) ($sala->assessment->questions_count ?? 0),
        ]);
    }

    /**
     * Atualiza nome, revisão coletiva e limite de alterações da sala de aula.
     */
    public function update(Request $request, Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);
        $sala->load(['assessment' => fn ($query) => $query->withCount('questions')]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'game_mode' => ['nullable', 'string', 'in:corujinha,corujao'],
            'collective_review_enabled' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->filled('starts_at') && $request->filled('ends_at')) {
                        $start = $request->date('starts_at');
                        $end = $request->date('ends_at');
                        if ($start && $end && $end->lte($start)) {
                            $fail('A data e hora de fechamento deve ser posterior à data de abertura.');
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Informe o nome da turma ou sala.',
        ]);

        $gameMode = $request->input('game_mode', $sala->game_mode ?? 'corujinha') === 'corujao' ? 'corujao' : 'corujinha';
        $reviewEnabled = $gameMode === 'corujao' ? false : $request->boolean('collective_review_enabled');
        $questionCount = (int) ($sala->assessment->questions_count ?? 0);
        $maxReviewChanges = $gameMode === 'corujao'
            ? null
            : $this->resolveValidatedMaxReviewChanges($request, $questionCount, $reviewEnabled);

        $sala->update([
            'name' => $validated['name'],
            'game_mode' => $gameMode,
            'collective_review_enabled' => $reviewEnabled,
            'max_review_changes' => $maxReviewChanges,
            'starts_at' => $request->filled('starts_at') ? $request->date('starts_at') : null,
            'ends_at' => $request->filled('ends_at') ? $request->date('ends_at') : null,
        ]);

        BroadcastService::dispatch(new ClassroomListUpdated($sala, 'updated'));

        return redirect()->route('professor.salas.show', $sala)
            ->with('success', 'Configurações da sala atualizadas com sucesso!');
    }

    /**
     * Fecha definitivamente a sala de aula para novas respostas (irreversível).
     */
    public function toggleStatus(Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);

        if (! $sala->is_active) {
            return back()->with('error', 'Esta sala já foi fechada e não pode mais ser reaberta.');
        }

        $this->closeClassroomAndNotify($sala);

        return back()->with('success', 'A sala foi fechada definitivamente para novas respostas.');
    }

    /**
     * Fecha a sala (se aberta) e direciona o professor para a tela de Devolutiva.
     */
    public function enterDevolutiva(Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);

        if ($sala->is_active) {
            $this->closeClassroomAndNotify($sala);
        }

        return redirect()->route('professor.salas.devolutiva', $sala);
    }

    /**
     * Tela de Devolutiva Pedagógica da Sala com gabarito, marcações, comentários e acertos/erros nominais.
     */
    public function devolutiva(Classroom $sala): View
    {
        $this->authorizeClassroom($sala);

        if ($sala->is_active) {
            $this->closeClassroomAndNotify($sala);
        }

        $sala->load([
            'assessment.questions.options',
            'studentAttempts.answers',
        ]);

        $questions = $sala->assessment->questions;
        $attempts = $sala->studentAttempts->sortBy('student_name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $questionReports = $questions->map(function ($question) use ($attempts) {
            $correctStudents = [];
            $wrongStudents = [];
            $unansweredStudents = [];
            $optionCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];

            foreach ($attempts as $attempt) {
                /** @var StudentAnswer|null $ans */
                $ans = $attempt->answers->firstWhere('question_id', $question->id);

                if (! $ans || (! $ans->initial_answer && ! $ans->reviewed_answer)) {
                    $unansweredStudents[] = $attempt->student_name;

                    continue;
                }

                $initial = $ans->initial_answer ? trim(strtoupper($ans->initial_answer)) : null;
                $reviewed = $ans->reviewed_answer ? trim(strtoupper($ans->reviewed_answer)) : null;
                $final = $reviewed ?: $initial;
                $changed = ($reviewed !== null && $initial !== null && $reviewed !== $initial);

                if ($final && array_key_exists($final, $optionCounts)) {
                    $optionCounts[$final]++;
                }

                $isCorrect = ($attempt->status === 'reviewed' || $reviewed !== null)
                    ? (bool) $ans->is_correct_final
                    : (bool) $ans->is_correct_initial;

                $entry = [
                    'name' => $attempt->student_name,
                    'initial' => $initial,
                    'final' => $final,
                    'changed' => $changed,
                ];

                if ($isCorrect) {
                    $correctStudents[] = $entry;
                } else {
                    $wrongStudents[] = $entry;
                }
            }

            $totalAttempts = $attempts->count();
            $correctCount = count($correctStudents);
            $accuracyRate = $totalAttempts > 0 ? round(($correctCount / $totalAttempts) * 100) : 0;

            return [
                'question' => $question,
                'expectedMarkers' => $question->expectedHighlightMarkers(),
                'correctCount' => $correctCount,
                'wrongCount' => count($wrongStudents),
                'unansweredCount' => count($unansweredStudents),
                'totalAttempts' => $totalAttempts,
                'accuracyRate' => $accuracyRate,
                'correctStudents' => $correctStudents,
                'wrongStudents' => $wrongStudents,
                'unansweredStudents' => $unansweredStudents,
                'optionCounts' => $optionCounts,
            ];
        });

        return view('professor.salas.devolutiva', [
            'classroom' => $sala,
            'attempts' => $attempts,
            'questionReports' => $questionReports,
        ]);
    }

    /**
     * Exibe o histórico detalhado da tentativa de um estudante (resposta inicial vs. revisão).
     */
    public function showAttempt(Classroom $sala, StudentAttempt $tentativa): View
    {
        $this->authorizeClassroom($sala);

        if ($tentativa->classroom_id !== $sala->id) {
            abort(404);
        }

        $tentativa->load([
            'classroom.assessment.questions.options',
            'answers.question.options',
        ]);

        $questions = $sala->assessment->questions;
        $answers = $tentativa->answers->keyBy('question_id');

        return view('professor.salas.tentativa-detalhes', [
            'classroom' => $sala,
            'attempt' => $tentativa,
            'questions' => $questions,
            'answers' => $answers,
        ]);
    }

    /**
     * Autoriza uma tentativa em dispositivo compartilhado que estava aguardando liberação.
     */
    public function authorizeAttempt(Classroom $sala, StudentAttempt $tentativa): RedirectResponse
    {
        $this->authorizeClassroom($sala);

        if ((int) $tentativa->classroom_id !== (int) $sala->id) {
            abort(404);
        }

        $tentativa->authorizeDevice();

        BroadcastService::dispatch(new StudentAttemptUpdated($tentativa));

        return back()->with('success', "Acesso do estudante {$tentativa->student_name} autorizado com sucesso!");
    }

    /**
     * Exclui a resposta / tentativa de um estudante na sala de aula.
     */
    public function destroyAttempt(Request $request, Classroom $sala, StudentAttempt $tentativa): RedirectResponse
    {
        $this->authorizeClassroom($sala);

        if ((int) $tentativa->classroom_id !== (int) $sala->id) {
            abort(404);
        }

        $studentName = $tentativa->student_name;
        $tentativa->delete();

        if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), '/tentativas/')) {
            return redirect()->route('professor.salas.show', $sala)
                ->with('success', "Resposta do estudante {$studentName} excluída com sucesso.");
        }

        return back()->with('success', "Resposta do estudante {$studentName} excluída com sucesso.");
    }

    /**
     * Realiza a correção manual de uma resposta do estudante pelo professor.
     */
    public function gradeAnswer(
        Request $request,
        Classroom $sala,
        StudentAttempt $tentativa,
        StudentAnswer $resposta
    ): RedirectResponse {
        $this->authorizeClassroom($sala);

        if ((int) $tentativa->classroom_id !== (int) $sala->id || (int) $resposta->student_attempt_id !== (int) $tentativa->id) {
            abort(404);
        }

        $validated = $request->validate([
            'manual_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_correct' => ['required', 'in:correct,partial,incorrect'],
            'teacher_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $score = (float) $validated['manual_score'];
        $isCorrect = $validated['is_correct'] === 'correct' || ($validated['is_correct'] === 'partial' && $score > 0);

        $resposta->update([
            'is_manually_graded' => true,
            'manual_score' => $score,
            'score' => $score,
            'is_correct_final' => $isCorrect,
            'teacher_comment' => $validated['teacher_comment'] ?? null,
        ]);

        $tentativa->recalculateScore();

        return back()->with('success', 'Correção da questão salva com sucesso!');
    }

    /**
     * Exclui uma sala.
     */
    public function destroy(Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);
        BroadcastService::dispatch(new ClassroomListUpdated($sala, 'deleted'));
        $sala->delete();

        return redirect()->route('professor.salas.index')
            ->with('success', 'Sala de aula excluída com sucesso.');
    }

    private function authorizeClassroom(Classroom $classroom): void
    {
        if ($classroom->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }

    private function closeClassroomAndNotify(Classroom $sala): void
    {
        if ($sala->close()) {
            BroadcastService::dispatch(new ClassroomClosed($sala));
            BroadcastService::dispatch(new ClassroomListUpdated($sala, 'updated'));
        }
    }

    private function resolveValidatedMaxReviewChanges(Request $request, int $questionCount, bool $reviewEnabled): ?int
    {
        if (! $reviewEnabled) {
            return null;
        }

        if (! $request->filled('max_review_changes')) {
            return $questionCount > 0 ? $questionCount : null;
        }

        $rules = ['required', 'integer', 'min:1'];
        if ($questionCount > 0) {
            $rules[] = "max:{$questionCount}";
        }

        $validated = $request->validate([
            'max_review_changes' => $rules,
        ], [
            'max_review_changes.integer' => 'A quantidade de questões na revisão deve ser um número inteiro.',
            'max_review_changes.min' => 'A quantidade mínima de questões que podem ser alteradas na revisão é 1.',
            'max_review_changes.max' => "A quantidade máxima não pode exceder as {$questionCount} questões desta avaliação.",
        ]);

        return (int) $validated['max_review_changes'];
    }
}
