<?php

namespace App\Http\Controllers\Student;

use App\Events\StudentAttemptUpdated;
use App\Events\StudentJoinedClassroom;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentAttempt;
use App\Services\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomPlayerController extends Controller
{
    /**
     * Tela inicial para o estudante informar o código da sala e seu nome.
     */
    public function showEnter(Request $request): View
    {
        $code = $request->query('code', '');

        return view('estudante.entrar', compact('code'));
    }

    /**
     * Valida a sala e cria a tentativa do estudante.
     */
    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'student_name' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'code.required' => 'Informe o código da sala de aula.',
            'student_name.required' => 'Informe o seu nome completo.',
            'student_name.min' => 'O nome do estudante deve ter pelo menos 3 caracteres.',
        ]);

        $code = strtoupper(trim($validated['code']));
        $classroom = Classroom::where('code', $code)->first();

        if (! $classroom) {
            return back()->withInput()->withErrors([
                'code' => 'Sala de aula não encontrada. Verifique o código com seu professor.',
            ]);
        }

        if (! $classroom->is_active) {
            return back()->withInput()->withErrors([
                'code' => 'Esta sala de aula está fechada para novas respostas.',
            ]);
        }

        $attempt = StudentAttempt::create([
            'classroom_id' => $classroom->id,
            'student_name' => trim($validated['student_name']),
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        BroadcastService::dispatch(new StudentJoinedClassroom($attempt));

        session([
            'current_attempt_id' => $attempt->id,
            'current_classroom_code' => $classroom->code,
        ]);

        return redirect()->route('estudante.player', ['code' => $classroom->code]);
    }

    /**
     * Tela de execução da avaliação (player de questões).
     */
    public function player(string $code): View|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        $classroom->load([
            'assessment.questions.options',
        ]);

        $questions = $classroom->assessment->questions;

        // Mapeia perguntas para o formato do player
        $questionsData = $questions->map(function (Question $q, $idx) use ($attempt) {
            $savedAnswer = StudentAnswer::where('student_attempt_id', $attempt->id)
                ->where('question_id', $q->id)
                ->first();

            return array_merge($q->toPlayerPayload(), [
                'index' => $idx + 1,
                'points' => (float) ($q->pivot->points ?? 1.0),
                'saved' => $savedAnswer ? [
                    'answer' => $savedAnswer->initial_answer,
                    'reason' => $savedAnswer->initial_reason,
                    'markers' => $savedAnswer->initial_markers ?? [],
                    'timeUsed' => $savedAnswer->time_spent_seconds * 1000,
                ] : null,
            ]);
        })->values()->all();

        return view('estudante.player', [
            'classroom' => $classroom,
            'attempt' => $attempt,
            'questionsData' => $questionsData,
        ]);
    }

    /**
     * Salva a resposta de uma questão via requisição web (JSON).
     */
    public function saveAnswer(Request $request, string $code): JsonResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return response()->json(['error' => 'Sessão expirada. Faça login na sala novamente.'], 401);
        }

        if ($request->has('answer')) {
            $request->merge(['answer' => trim(strtoupper($request->input('answer')))]);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'answer' => ['required', 'in:A,B,C,D,E'],
            'reason' => ['required', 'string'],
            'markers' => ['nullable', 'array'],
            'time_spent' => ['nullable', 'integer'],
        ]);

        $question = Question::findOrFail($validated['question_id']);
        $givenAnswer = trim(strtoupper($validated['answer']));
        $correctAnswer = trim(strtoupper($question->correct_option));
        $isCorrect = ($givenAnswer === $correctAnswer);

        // Validação das regras de marca-texto da questão
        $markers = $validated['markers'] ?? [];
        $hasCommandMarker = collect($markers)->contains('kind', 'command');
        $hasClueMarker = collect($markers)->contains(fn ($m) => in_array($m['kind'] ?? '', ['evidence', 'clue']));

        if ($question->require_command_highlight && ! $hasCommandMarker) {
            return response()->json([
                'error' => 'Para avançar, você deve destacar o comando da questão com o Marca-Texto Amarelo.',
            ], 422);
        }

        if ($question->require_clue_highlight && ! $hasClueMarker) {
            return response()->json([
                'error' => 'Para avançar, você deve destacar as pistas no texto de apoio com o Marca-Texto Azul.',
            ], 422);
        }

        $seconds = max(0, (int) round(($validated['time_spent'] ?? 0) / 1000));

        StudentAnswer::updateOrCreate(
            [
                'student_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'initial_answer' => $givenAnswer,
                'initial_reason' => $validated['reason'],
                'initial_markers' => $markers,
                'is_correct_initial' => $isCorrect,
                'is_correct_final' => $isCorrect,
                'time_spent_seconds' => $seconds,
            ]
        );

        $attempt->recalculateScore();

        BroadcastService::dispatch(new StudentAttemptUpdated($attempt));

        return response()->json(['success' => true]);
    }

    /**
     * Finaliza a etapa de resolução inicial.
     */
    public function submitAttempt(Request $request, string $code): JsonResponse|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        $attempt->update([
            'completed_at' => now(),
            'status' => 'submitted',
        ]);
        $attempt->recalculateScore();

        BroadcastService::dispatch(new StudentAttemptUpdated($attempt));

        // Se a sala tem revisão coletiva ativada, direciona para a tela de revisão
        if ($classroom->collective_review_enabled) {
            $nextUrl = route('estudante.revisao', ['code' => $classroom->code]);
        } else {
            // Se não tem revisão coletiva, direciona imediatamente para o resultado
            $nextUrl = route('estudante.resultado', ['code' => $classroom->code]);
        }

        if ($request->wantsJson()) {
            return response()->json(['redirect_url' => $nextUrl]);
        }

        return redirect($nextUrl);
    }

    /**
     * Tela de Revisão e Correção Coletiva.
     */
    public function showReview(string $code): View|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();

        // Se a sala não permite revisão coletiva, pula direto para o resultado
        if (! $classroom->collective_review_enabled) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        $attempt = $this->getAttempt($classroom);
        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        $classroom->load(['assessment.questions.options']);
        $answers = $attempt->answers()->with('question.options')->get()->keyBy('question_id');

        return view('estudante.revisao', [
            'classroom' => $classroom,
            'attempt' => $attempt,
            'questions' => $classroom->assessment->questions,
            'answers' => $answers,
        ]);
    }

    /**
     * Salva a revisão do aluno e conclui.
     */
    public function finishReview(Request $request, string $code): RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        $reviews = $request->input('reviews', []);

        foreach ($reviews as $questionId => $data) {
            $question = Question::find($questionId);
            if (! $question) {
                continue;
            }

            $revAnswer = isset($data['reviewed_answer']) ? trim(strtoupper($data['reviewed_answer'])) : null;
            $reflection = $data['review_reflection'] ?? null;

            $answerRecord = StudentAnswer::where('student_attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->first();

            if ($answerRecord) {
                $finalLetter = $revAnswer ?: $answerRecord->initial_answer;
                $isCorrectFinal = (trim(strtoupper($finalLetter)) === trim(strtoupper($question->correct_option)));

                $answerRecord->update([
                    'reviewed_answer' => $finalLetter,
                    'review_reflection' => $reflection,
                    'is_correct_final' => $isCorrectFinal,
                ]);
            }
        }

        $attempt->update([
            'reviewed_at' => now(),
            'status' => 'reviewed',
        ]);
        $attempt->recalculateScore();

        BroadcastService::dispatch(new StudentAttemptUpdated($attempt));

        return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
    }

    /**
     * Tela de Resultados & Devolutivas Pedagógicas.
     */
    public function showResult(string $code): View|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        $classroom->load(['assessment.questions.options']);
        $questions = $classroom->assessment->questions;
        $answers = $attempt->answers()->with('question.options')->get()->keyBy('question_id');

        $totalQuestions = $questions->count();
        $score = $attempt->recalculateScore();
        $earnedPoints = $attempt->earnedPoints();
        $totalPossiblePoints = $attempt->totalPossiblePoints();

        return view('estudante.resultado', [
            'classroom' => $classroom,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
            'totalQuestions' => $totalQuestions,
            'score' => $score,
            'earnedPoints' => $earnedPoints,
            'totalPossiblePoints' => $totalPossiblePoints,
        ]);
    }

    private function getAttempt(Classroom $classroom): ?StudentAttempt
    {
        $attemptId = session('current_attempt_id');
        if (! $attemptId) {
            return null;
        }

        return StudentAttempt::where('id', $attemptId)
            ->where('classroom_id', $classroom->id)
            ->first();
    }
}
