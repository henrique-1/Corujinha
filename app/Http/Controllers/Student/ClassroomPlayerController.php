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
use App\Services\MathComparisonService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $missKey = $this->classroomMissLimiterKey($request);
        if (RateLimiter::tooManyAttempts($missKey, 20)) {
            abort(429, 'Muitas tentativas com códigos inválidos. Aguarde um minuto.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'student_name' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'code.required' => 'Informe o código da sala de aula.',
            'student_name.required' => 'Informe o seu nome completo.',
            'student_name.min' => 'O nome do estudante deve ter pelo menos 3 caracteres.',
        ]);

        $code = strtoupper(trim($validated['code']));
        $classroom = Classroom::where('code', $code)->first();

        if (! $classroom) {
            RateLimiter::hit($missKey, 60);

            return back()->withInput()->withErrors([
                'code' => 'Sala de aula não encontrada. Verifique o código com seu professor.',
            ]);
        }

        $availability = $classroom->accessAvailability();
        if (! $availability['available']) {
            return back()->withInput()->withErrors([
                'code' => $availability['message'],
            ]);
        }

        $deviceIdentifier = $request->input('device_identifier');
        $deviceIdentifier = is_string($deviceIdentifier) && trim($deviceIdentifier) !== '' ? trim($deviceIdentifier) : null;
        $isAuthorized = true;
        $status = 'in_progress';

        // Verificação de dispositivo na sala de aula
        if ($deviceIdentifier) {
            $existingAttempt = StudentAttempt::where('classroom_id', $classroom->id)
                ->where('device_identifier', $deviceIdentifier)
                ->latest()
                ->first();

            if ($existingAttempt) {
                $normalizedInputName = mb_strtolower(trim($validated['student_name']));
                $normalizedExistingName = mb_strtolower(trim($existingAttempt->student_name));

                // Aluno repetido que já concluiu neste dispositivo
                if ($normalizedInputName === $normalizedExistingName && in_array($existingAttempt->status, ['submitted', 'reviewed'], true)) {
                    return back()->withInput()->withErrors([
                        'code' => 'Você já respondeu a esta avaliação neste dispositivo. Se precisar responder novamente, solicite ao seu professor que remova a sua resposta anterior no painel da sala.',
                    ]);
                }

                // Dispositivo compartilhado (laboratório de informática / outro aluno usando a mesma máquina)
                $status = 'waiting_authorization';
                $isAuthorized = false;
            }
        }

        $attempt = StudentAttempt::create([
            'classroom_id' => $classroom->id,
            'student_name' => trim($validated['student_name']),
            'device_identifier' => $deviceIdentifier,
            'device_ip' => $request->ip(),
            'is_device_authorized' => $isAuthorized,
            'started_at' => now(),
            'status' => $status,
        ]);

        BroadcastService::dispatch(new StudentJoinedClassroom($attempt));

        $request->session()->regenerate();
        session([
            'current_attempt_id' => $attempt->id,
            'current_classroom_code' => $classroom->code,
        ]);

        if (! $isAuthorized) {
            return redirect()->route('estudante.aguardando_autorizacao', ['code' => $classroom->code]);
        }

        return redirect()->route('estudante.player', ['code' => $classroom->code]);
    }

    /**
     * Retoma uma tentativa existente a partir do Código da Sala + Código do Aluno.
     */
    public function resume(Request $request): RedirectResponse
    {
        $missKey = $this->resumeMissLimiterKey($request);
        if (RateLimiter::tooManyAttempts($missKey, 20)) {
            abort(429, 'Muitas tentativas com códigos inválidos. Aguarde um minuto.');
        }

        $request->merge([
            'resume_code' => is_string($request->input('resume_code')) ? strtoupper(trim($request->input('resume_code'))) : null,
        ]);

        $validated = $request->validate([
            'resume_code' => ['required', 'string', 'max:20'],
            'access_code' => ['required', 'string', 'max:20'],
        ], [
            'resume_code.required' => 'Informe o código da sala de aula.',
            'access_code.required' => 'Informe o seu código de aluno (ALU-XXXXXXXXXX).',
        ]);

        $classroom = Classroom::where('code', $validated['resume_code'])->first();
        $attempt = $classroom
            ? StudentAttempt::where('classroom_id', $classroom->id)
                ->where('access_code', StudentAttempt::normalizeAccessCode($validated['access_code']))
                ->first()
            : null;

        if (! $classroom || ! $attempt) {
            RateLimiter::hit($missKey, 60);

            return back()->withInput()->withErrors([
                'access_code' => 'Não encontramos uma participação com esses códigos. Confira o código da sala e o código do aluno anotado.',
            ]);
        }

        RateLimiter::clear($missKey);

        $request->session()->regenerate();
        session([
            'current_attempt_id' => $attempt->id,
            'current_classroom_code' => $classroom->code,
        ]);

        $availability = $classroom->accessAvailability();
        if (! $availability['available']) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code])
                ->with('info', $availability['message'] ?? 'A avaliação foi finalizada pelo professor. Confira abaixo seu resultado e a devolutiva.');
        }

        if ($attempt->status === 'in_progress') {
            return redirect()->route('estudante.player', ['code' => $classroom->code])
                ->with('info', 'Bem-vindo de volta! Você continua de onde parou.');
        }

        if ($classroom->collective_review_enabled && $attempt->status === 'submitted') {
            return redirect()->route('estudante.revisao', ['code' => $classroom->code]);
        }

        return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
    }

    /**
     * Tela de execução da avaliação (player de questões).
     */
    public function player(Request $request, string $code): View|RedirectResponse
    {
        $missKey = $this->classroomMissLimiterKey($request);
        if (RateLimiter::tooManyAttempts($missKey, 20)) {
            abort(429, 'Muitas tentativas com códigos inválidos. Aguarde um minuto.');
        }

        $classroom = Classroom::where('code', strtoupper($code))->first();
        if (! $classroom) {
            RateLimiter::hit($missKey, 60);
            abort(404);
        }

        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        if ($attempt->isWaitingAuthorization()) {
            return redirect()->route('estudante.aguardando_autorizacao', ['code' => $classroom->code]);
        }

        $availability = $classroom->accessAvailability();
        if (! $availability['available']) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code])
                ->with('info', $availability['message'] ?? 'A avaliação foi finalizada pelo professor. Confira abaixo seu resultado e a devolutiva.');
        }

        if ($attempt->status !== 'in_progress') {
            if ($classroom->collective_review_enabled && $attempt->status === 'submitted') {
                return redirect()->route('estudante.revisao', ['code' => $classroom->code]);
            }

            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        $classroom->load([
            'assessment.questions.options',
        ]);

        $questions = $classroom->assessment->questions;
        $savedAnswersByQuestionId = $attempt->answers()->get()->keyBy('question_id');

        // Mapeia perguntas para o formato do player
        $questionsData = $questions->map(function (Question $q, $idx) use ($savedAnswersByQuestionId) {
            $savedAnswer = $savedAnswersByQuestionId->get($q->id);

            return array_merge($q->toPlayerPayload(), [
                'index' => $idx + 1,
                'points' => (float) ($q->pivot->points ?? 1.0),
                'saved' => $savedAnswer ? [
                    'answer' => $savedAnswer->initial_answer,
                    'reason' => $savedAnswer->initial_reason,
                    'markers' => $savedAnswer->initial_markers ?? [],
                    'resolution_image_url' => $savedAnswer->resolution_image_url,
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
     * Tela de espera para dispositivo compartilhado aguardando autorização do professor.
     */
    public function waitingAuthorization(string $code): View|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        if (! $attempt->isWaitingAuthorization()) {
            return redirect()->route('estudante.player', ['code' => $classroom->code]);
        }

        return view('estudante.aguardando-autorizacao', [
            'classroom' => $classroom,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Endpoint para verificação de status de autorização do dispositivo (polling leve).
     */
    public function checkAuthorizationStatus(string $code): JsonResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return response()->json([
                'authorized' => false,
                'redirect' => route('estudante.entrar', ['code' => $classroom->code], false),
            ], 401);
        }

        return response()->json([
            'authorized' => ! $attempt->isWaitingAuthorization(),
            'status' => $attempt->status,
            'redirect' => route('estudante.player', ['code' => $classroom->code], false),
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

        $availability = $classroom->accessAvailability();
        if (! $availability['available']) {
            return response()->json([
                'closed' => true,
                'redirect_url' => route('estudante.resultado', ['code' => $classroom->code], false),
                'error' => $availability['message'] ?? 'A avaliação foi finalizada pelo professor.',
            ], 422);
        }

        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'error' => 'As respostas iniciais já foram enviadas e não podem mais ser alteradas.',
            ], 422);
        }

        $questionIdentifier = $request->input('question_id');
        if (! is_string($questionIdentifier) && ! is_int($questionIdentifier)) {
            return response()->json(['error' => 'Identificador de questão inválido.'], 422);
        }

        $question = $classroom->assessment
            ->questions()
            ->where(function ($query) use ($questionIdentifier) {
                if (is_int($questionIdentifier) || ctype_digit((string) $questionIdentifier)) {
                    $query->where('questions.id', (int) $questionIdentifier);
                } else {
                    $query->where('questions.uuid', (string) $questionIdentifier);
                }
            })
            ->first();

        if (! $question) {
            return response()->json([
                'error' => 'Esta questão não pertence à avaliação desta sala de aula.',
            ], 422);
        }

        $isDirectInput = $question->isDirectInput();
        $isTimeout = $request->boolean('is_timeout');

        // Sanitização de resposta vazia para null
        $rawAnswer = $request->input('answer');
        if (is_string($rawAnswer)) {
            $rawAnswer = trim($rawAnswer);
            if ($rawAnswer === '') {
                $rawAnswer = null;
            } elseif (! $isDirectInput) {
                $rawAnswer = strtoupper($rawAnswer);
            }
        }
        $request->merge(['answer' => $rawAnswer]);

        if ($isDirectInput) {
            $validated = $request->validate([
                'question_id' => ['required'],
                'answer' => [$isTimeout ? 'nullable' : 'required', 'nullable', 'string', 'max:2000'],
                'reason' => ['nullable', 'string', 'max:5000'],
                'resolution_image' => ['nullable', 'image', 'max:10240'],
                'time_spent' => ['nullable', 'integer', 'min:0'],
            ], [
                'answer.required' => 'Digite a sua resposta para a questão.',
                'resolution_image.image' => 'O arquivo da resolução deve ser uma imagem válida (JPG, PNG, WEBP).',
                'resolution_image.max' => 'A imagem da resolução deve ter no máximo 10 MB.',
            ]);

            $givenAnswer = isset($validated['answer']) && trim((string) $validated['answer']) !== ''
                ? trim((string) $validated['answer'])
                : null;

            if ($givenAnswer === null) {
                $isCorrect = false;
            } else {
                $mathService = app(MathComparisonService::class);
                $isCorrect = $mathService->isEquivalent($givenAnswer, $question->expected_answer);
            }
            $markers = [];
        } else {
            $validated = $request->validate([
                'question_id' => ['required'],
                'answer' => [$isTimeout ? 'nullable' : 'required', 'nullable', 'string', 'in:A,B,C,D,E'],
                'reason' => [$isTimeout ? 'nullable' : 'required', 'nullable', 'string', 'max:5000'],
                'markers' => ['nullable', 'array', 'max:200'],
                'markers.*' => ['required', 'array'],
                'markers.*.id' => ['nullable', 'string', 'max:64'],
                'markers.*.kind' => ['required', 'string', 'in:command,evidence,clue'],
                'markers.*.target' => ['nullable', 'string', 'in:source,support,prompt,title'],
                'markers.*.text' => ['nullable', 'string', 'max:2000'],
                'markers.*.start' => ['nullable', 'integer', 'min:0'],
                'markers.*.end' => ['nullable', 'integer', 'min:0'],
                'time_spent' => ['nullable', 'integer', 'min:0'],
            ]);

            $givenAnswer = isset($validated['answer']) && trim((string) $validated['answer']) !== ''
                ? trim(strtoupper((string) $validated['answer']))
                : null;
            $correctAnswer = trim(strtoupper((string) $question->correct_option));
            $isCorrect = ($givenAnswer !== null && $givenAnswer !== '' && $givenAnswer === $correctAnswer);

            $markers = array_values(array_map(function (array $m): array {
                return [
                    'id' => isset($m['id']) ? (string) $m['id'] : null,
                    'kind' => (string) $m['kind'],
                    'target' => isset($m['target']) ? (string) $m['target'] : null,
                    'text' => isset($m['text']) && is_string($m['text']) ? $m['text'] : '',
                    'start' => isset($m['start']) ? (int) $m['start'] : null,
                    'end' => isset($m['end']) ? (int) $m['end'] : null,
                ];
            }, $validated['markers'] ?? []));

            if (! $isTimeout) {
                $hasCommandMarker = collect($markers)->contains('kind', 'command');
                $hasClueMarker = collect($markers)->contains(fn ($m) => in_array($m['kind'] ?? '', ['evidence', 'clue'], true));

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
            }
        }

        $seconds = max(0, (int) round(($validated['time_spent'] ?? 0) / 1000));
        $questionPoints = (float) ($question->pivot->points ?? 1.0);
        $score = $isCorrect ? $questionPoints : 0.0;

        $attributes = [
            'student_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ];
        $values = [
            'initial_answer' => $givenAnswer,
            'initial_reason' => $validated['reason'] ?? '',
            'initial_markers' => $markers,
            'is_correct_initial' => $isCorrect,
            'is_correct_final' => $isCorrect,
            'score' => $score,
            'time_spent_seconds' => $seconds,
        ];

        if ($request->hasFile('resolution_image')) {
            $imagePath = $request->file('resolution_image')->store('student_resolutions', 'local');
            $values['resolution_image_path'] = $imagePath;
        }

        try {
            $saved = StudentAnswer::updateOrCreate($attributes, $values);
        } catch (UniqueConstraintViolationException) {
            StudentAnswer::where($attributes)->update($values);
            $saved = StudentAnswer::where($attributes)->first();
        }

        $attempt->recalculateScore();

        BroadcastService::dispatch(new StudentAttemptUpdated($attempt));

        return response()->json([
            'success' => true,
            'resolution_image_url' => $saved?->resolution_image_url,
        ]);
    }

    /**
     * Serve a imagem de resolução enviada pelo aluno com autenticação segura.
     */
    public function showResolutionImage(StudentAnswer $studentAnswer): StreamedResponse
    {
        $user = Auth::user();
        $attemptId = session('current_attempt_id');

        $isStudentOwner = ($attemptId && (int) $attemptId === (int) $studentAnswer->student_attempt_id);
        $isTeacherOwner = ($user && $studentAnswer->attempt && $studentAnswer->attempt->classroom && ((int) $user->id === (int) $studentAnswer->attempt->classroom->user_id || $user->isSuperAdmin()));

        if (! $isStudentOwner && ! $isTeacherOwner) {
            abort(403, 'Acesso não autorizado a esta imagem de resolução.');
        }

        if (! $studentAnswer->resolution_image_path || ! Storage::disk('local')->exists($studentAnswer->resolution_image_path)) {
            abort(404, 'Imagem de resolução não encontrada.');
        }

        return Storage::disk('local')->response(
            $studentAnswer->resolution_image_path,
            null,
            ['Cache-Control' => 'private, max-age=3600']
        );
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

        if (! $classroom->is_active) {
            if ($request->wantsJson()) {
                return response()->json([
                    'closed' => true,
                    'redirect_url' => route('estudante.resultado', ['code' => $classroom->code], false),
                    'error' => 'A avaliação foi finalizada pelo professor. Você será redirecionado para a tela de resultado e devolutiva.',
                ], 422);
            }

            return redirect()->route('estudante.resultado', ['code' => $classroom->code])
                ->with('info', 'A avaliação foi finalizada pelo professor. Confira abaixo seu resultado e a devolutiva.');
        }

        if ($attempt->status === 'in_progress') {
            $attempt->update([
                'completed_at' => now(),
                'status' => 'submitted',
            ]);
            $attempt->recalculateScore();

            BroadcastService::dispatch(new StudentAttemptUpdated($attempt));
        }

        if ($classroom->collective_review_enabled && $attempt->status === 'submitted') {
            $nextUrl = route('estudante.revisao', ['code' => $classroom->code]);
        } else {
            $nextUrl = route('estudante.resultado', ['code' => $classroom->code]);
        }

        if ($request->wantsJson()) {
            return response()->json(['redirect_url' => $nextUrl]);
        }

        return redirect($nextUrl);
    }

    /**
     * Registra quando o estudante sai da aba de resolução ou perde o foco.
     */
    public function recordTabSwitch(Request $request, string $code): JsonResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return response()->json(['error' => 'Sessão expirada.'], 401);
        }

        $reason = $request->input('reason', 'visibility_hidden');
        $newCount = $attempt->recordTabSwitch($reason);

        BroadcastService::dispatch(new StudentAttemptUpdated($attempt));

        return response()->json([
            'success' => true,
            'tab_switches_count' => $newCount,
        ]);
    }

    /**
     * Tela de Revisão e Correção Coletiva.
     */
    public function showReview(string $code): View|RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();

        if (! $classroom->collective_review_enabled) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        $attempt = $this->getAttempt($classroom);
        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        if ($attempt->isWaitingAuthorization()) {
            return redirect()->route('estudante.aguardando_autorizacao', ['code' => $classroom->code]);
        }

        $availability = $classroom->accessAvailability();
        if (! $availability['available']) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code])
                ->with('info', $availability['message'] ?? 'A avaliação foi finalizada pelo professor. Confira abaixo seu resultado e a devolutiva.');
        }

        if ($attempt->status === 'in_progress') {
            return redirect()->route('estudante.player', ['code' => $classroom->code]);
        }

        if ($attempt->status === 'reviewed') {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        $classroom->load(['assessment.questions.options']);
        $answers = $attempt->answers()->with('question.options')->get()->keyBy('question_id');
        $maxReviewChanges = $classroom->effectiveMaxReviewChanges();

        return view('estudante.revisao', [
            'classroom' => $classroom,
            'attempt' => $attempt,
            'questions' => $classroom->assessment->questions,
            'answers' => $answers,
            'maxReviewChanges' => $maxReviewChanges,
        ]);
    }

    /**
     * Salva a revisão do aluno e conclui (transição única de submitted -> reviewed).
     */
    public function finishReview(Request $request, string $code): RedirectResponse
    {
        $classroom = Classroom::where('code', strtoupper($code))->firstOrFail();
        $attempt = $this->getAttempt($classroom);

        if (! $attempt) {
            return redirect()->route('estudante.entrar', ['code' => $classroom->code]);
        }

        if (! $classroom->is_active) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code])
                ->with('info', 'A avaliação foi finalizada pelo professor. Não é mais possível enviar alterações.');
        }

        if (! $classroom->collective_review_enabled) {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        if ($attempt->status === 'in_progress') {
            return redirect()->route('estudante.player', ['code' => $classroom->code]);
        }

        if ($attempt->status === 'reviewed') {
            return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
        }

        $classroom->loadMissing('assessment.questions');
        $assessmentQuestions = $classroom->assessment->questions;
        $questionsById = $assessmentQuestions->keyBy('id');
        $questionsByUuid = $assessmentQuestions->keyBy('uuid');
        $existingAnswers = $attempt->answers()->get()->keyBy('question_id');

        $maxQuestionsCount = max(1, $assessmentQuestions->count());

        $validated = $request->validate([
            'reviews' => ['nullable', 'array', 'max:'.$maxQuestionsCount],
            'reviews.*' => ['required', 'array'],
            'reviews.*.reviewed_answer' => ['nullable', 'string', 'in:A,B,C,D,E,a,b,c,d,e'],
            'reviews.*.review_reflection' => ['nullable', 'string', 'max:5000'],
        ]);

        $reviews = $validated['reviews'] ?? [];
        $maxChanges = $classroom->effectiveMaxReviewChanges();
        $changedCount = 0;

        /** @var array<int, array{question: Question, answerRecord: StudentAnswer, revAnswer: ?string, reflection: ?string}> $resolvedReviews */
        $resolvedReviews = [];

        foreach ($reviews as $questionKey => $data) {
            $keyStr = (string) $questionKey;
            $question = ctype_digit($keyStr)
                ? $questionsById->get((int) $keyStr)
                : $questionsByUuid->get($keyStr);

            if (! $question) {
                continue;
            }

            $answerRecord = $existingAnswers->get($question->id);
            if (! $answerRecord) {
                continue;
            }

            $revAnswer = isset($data['reviewed_answer']) && is_string($data['reviewed_answer']) && trim($data['reviewed_answer']) !== ''
                ? strtoupper(trim($data['reviewed_answer']))
                : null;
            $reflection = isset($data['review_reflection']) && is_string($data['review_reflection'])
                ? $data['review_reflection']
                : null;

            $initialAnswer = $answerRecord->initial_answer ? trim(strtoupper($answerRecord->initial_answer)) : null;

            if ($revAnswer && $initialAnswer && $revAnswer !== $initialAnswer) {
                $changedCount++;
            }

            $resolvedReviews[$question->id] = [
                'question' => $question,
                'answerRecord' => $answerRecord,
                'revAnswer' => $revAnswer,
                'reflection' => $reflection,
            ];
        }

        if ($changedCount > $maxChanges) {
            return back()
                ->withInput()
                ->withErrors([
                    'reviews' => "Você alterou {$changedCount} questão(ões), mas o limite configurado pelo professor para esta sala é de no máximo {$maxChanges} alteração(ões).",
                ]);
        }

        foreach ($resolvedReviews as $item) {
            $question = $item['question'];
            $answerRecord = $item['answerRecord'];
            $finalLetter = $item['revAnswer'] ?: $answerRecord->initial_answer;
            $isCorrectFinal = (trim(strtoupper((string) $finalLetter)) === trim(strtoupper($question->correct_option)));

            $answerRecord->update([
                'reviewed_answer' => $finalLetter,
                'review_reflection' => $item['reflection'],
                'is_correct_final' => $isCorrectFinal,
            ]);
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

        if ($classroom->is_active) {
            if ($attempt->status === 'in_progress') {
                return redirect()->route('estudante.player', ['code' => $classroom->code]);
            }

            if ($classroom->collective_review_enabled && $attempt->status !== 'reviewed') {
                return redirect()->route('estudante.revisao', ['code' => $classroom->code]);
            }
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

    private function classroomMissLimiterKey(Request $request): string
    {
        return 'sala-code-miss:'.$request->ip();
    }

    private function resumeMissLimiterKey(Request $request): string
    {
        return 'sala-resume-miss:'.$request->ip();
    }
}
