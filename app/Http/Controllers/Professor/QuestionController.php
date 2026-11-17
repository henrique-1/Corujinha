<?php

namespace App\Http\Controllers\Professor;

use App\Events\QuestionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\QuestionOption;
use App\Models\StudentAttempt;
use App\Models\TeachingArea;
use App\Services\BroadcastService;
use App\Services\RichTextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    /**
     * Lista as perguntas cadastradas pelo professor com filtros por turma/área.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->input('tab', 'minhas');

        if ($tab === 'compartilhadas') {
            $query = Question::with(['options', 'folder', 'user', 'shares.user'])
                ->where('user_id', '!=', $user->id)
                ->whereHas('shares', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('email', mb_strtolower($user->email));
                });
        } else {
            $query = $user->questions()->with(['options', 'folder', 'shares.user']);
        }

        if ($request->filled('area')) {
            $query->where('area', $request->input('area'));
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->input('grade_level'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('prompt', 'like', $term)
                    ->orWhere('support_title', 'like', $term)
                    ->orWhere('focus', 'like', $term);
            });
        }

        $questions = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $myQuestionsCount = $user->questions()->count();
        $sharedQuestionsCount = Question::where('user_id', '!=', $user->id)
            ->whereHas('shares', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })->count();

        $areas = $user->questions()->whereNotNull('area')->distinct()->pluck('area')->filter()->values();
        $gradeLevels = $user->questions()->whereNotNull('grade_level')->distinct()->pluck('grade_level')->filter()->values();

        $myFolders = $user->questionFolders()->withCount('questions')->latest()->get();
        $sharedFolders = QuestionFolder::whereHas('shares', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('email', mb_strtolower($user->email));
        })->withCount('questions')->with(['user', 'shares' => fn ($q) => $q->where('user_id', $user->id)->orWhere('email', mb_strtolower($user->email))])->latest()->get();

        return view('professor.perguntas.index', compact(
            'questions',
            'areas',
            'gradeLevels',
            'myFolders',
            'sharedFolders',
            'tab',
            'myQuestionsCount',
            'sharedQuestionsCount'
        ));
    }

    /**
     * Formulário de criação de pergunta.
     */
    public function create(): View
    {
        $suggestedAreas = TeachingArea::orderBy('name')->pluck('name');
        $suggestedGradeLevels = GradeLevel::orderBy('id')->pluck('name');
        $folders = Auth::user()->questionFolders()->orderBy('name')->get();

        return view('professor.perguntas.create', compact('suggestedAreas', 'suggestedGradeLevels', 'folders'));
    }

    /**
     * Armazena uma nova pergunta com suas opções e devolutivas.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQuestionData($request);

        $imagePath = null;
        if ($request->hasFile('support_image')) {
            $imagePath = $request->file('support_image')->store('questions/images', 'local');
        }

        $expectedAnswerImagePath = null;
        if ($request->hasFile('expected_answer_image')) {
            $expectedAnswerImagePath = $request->file('expected_answer_image')->store('questions/expected_answers', 'local');
        }

        $supportTitle = $validated['support_title'] ?? null;
        $supportText = RichTextSanitizer::sanitize($validated['support_text'] ?? null);
        $folderId = $this->resolveFolderId($validated['folder_id'] ?? null);
        $gradeLevel = GradeLevel::findOrCreateSimilar($validated['grade_level'] ?? null);
        $focusPayload = $this->resolveFocusPayload($request, $validated);

        $area = mb_strtolower(trim($validated['area'] ?? ''));
        $isMath = str_contains($area, 'matemát') || str_contains($area, 'matemat');
        $isPortuguese = $area !== '' && (str_contains($area, 'portugu') || str_contains($area, 'língua portuguesa'));
        $answerType = ($isMath && ($validated['answer_type'] ?? '') === 'direct_input')
            ? 'direct_input'
            : 'multiple_choice';

        $requireCommandHighlight = $isPortuguese
            ? $request->boolean('require_command_highlight', true)
            : false;

        $requireClueHighlight = $isPortuguese
            ? $request->boolean('require_clue_highlight', true)
            : false;

        $question = Question::create([
            'user_id' => Auth::id(),
            'folder_id' => $folderId,
            'focus' => $focusPayload['focus'],
            'focus_terms' => $focusPayload['focus_terms'],
            'area' => $validated['area'] ?? null,
            'answer_type' => $answerType,
            'expected_answer' => $answerType === 'direct_input' ? ($validated['expected_answer'] ?? null) : null,
            'expected_answer_explanation' => $validated['expected_answer_explanation'] ?? null,
            'expected_answer_image_path' => $expectedAnswerImagePath,
            'grade_level' => $gradeLevel,
            'time_limit_seconds' => (int) ($validated['time_limit_seconds'] ?? 180),
            'support_type' => $this->resolveSupportType($supportTitle, $supportText, $imagePath),
            'support_title' => $supportTitle,
            'support_text' => $supportText,
            'support_image_path' => $imagePath,
            'prompt' => $validated['prompt'],
            'require_command_highlight' => $requireCommandHighlight,
            'require_clue_highlight' => $requireClueHighlight,
            'expected_commands' => $isPortuguese ? ($validated['expected_commands'] ?? null) : null,
            'command_feedback' => $isPortuguese ? ($validated['command_feedback'] ?? null) : null,
            'expected_clues' => $isPortuguese ? ($validated['expected_clues'] ?? null) : null,
            'clue_feedback' => $isPortuguese ? ($validated['clue_feedback'] ?? null) : null,
            'correct_option' => $answerType === 'direct_input' ? null : ($validated['correct_option'] ?? null),
        ]);

        if ($answerType === 'multiple_choice' && ! empty($validated['options'])) {
            foreach ($validated['options'] as $opt) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'letter' => $opt['letter'],
                    'text' => $opt['text'],
                    'feedback' => $opt['feedback'] ?? null,
                ]);
            }
        }

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pergunta cadastrada com sucesso!');
    }

    /**
     * Formulário de edição de pergunta.
     */
    public function edit(Question $pergunta): View
    {
        $this->authorizeQuestion($pergunta);
        $pergunta->load('options');

        $suggestedAreas = TeachingArea::orderBy('name')->pluck('name');
        $suggestedGradeLevels = GradeLevel::orderBy('id')->pluck('name');
        $folders = Auth::user()->questionFolders()->orderBy('name')->get();

        return view('professor.perguntas.edit', [
            'question' => $pergunta,
            'suggestedAreas' => $suggestedAreas,
            'suggestedGradeLevels' => $suggestedGradeLevels,
            'folders' => $folders,
        ]);
    }

    /**
     * Atualiza a pergunta.
     */
    public function update(Request $request, Question $pergunta): RedirectResponse
    {
        $this->authorizeQuestion($pergunta);

        $validated = $this->validateQuestionData($request);

        $imagePath = $pergunta->support_image_path;
        if ($request->boolean('remove_support_image') && $imagePath) {
            $this->deleteSupportImageFile($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('support_image')) {
            if ($imagePath) {
                $this->deleteSupportImageFile($imagePath);
            }
            $imagePath = $request->file('support_image')->store('questions/images', 'local');
        }

        $expectedAnswerImagePath = $pergunta->expected_answer_image_path;
        if ($request->boolean('remove_expected_answer_image') && $expectedAnswerImagePath) {
            $this->deleteSupportImageFile($expectedAnswerImagePath);
            $expectedAnswerImagePath = null;
        }

        if ($request->hasFile('expected_answer_image')) {
            if ($expectedAnswerImagePath) {
                $this->deleteSupportImageFile($expectedAnswerImagePath);
            }
            $expectedAnswerImagePath = $request->file('expected_answer_image')->store('questions/expected_answers', 'local');
        }

        $supportTitle = $validated['support_title'] ?? null;
        $supportText = RichTextSanitizer::sanitize($validated['support_text'] ?? null);
        $folderId = array_key_exists('folder_id', $validated)
            ? $this->resolveFolderId($validated['folder_id'])
            : $pergunta->folder_id;
        $gradeLevel = GradeLevel::findOrCreateSimilar($validated['grade_level'] ?? null);
        $focusPayload = $this->resolveFocusPayload($request, $validated);

        $area = mb_strtolower(trim($validated['area'] ?? ''));
        $isMath = str_contains($area, 'matemát') || str_contains($area, 'matemat');
        $isPortuguese = $area !== '' && (str_contains($area, 'portugu') || str_contains($area, 'língua portuguesa'));
        $answerType = ($isMath && ($validated['answer_type'] ?? '') === 'direct_input')
            ? 'direct_input'
            : 'multiple_choice';

        $requireCommandHighlight = $isPortuguese
            ? $request->boolean('require_command_highlight', true)
            : false;

        $requireClueHighlight = $isPortuguese
            ? $request->boolean('require_clue_highlight', true)
            : false;

        $pergunta->update([
            'folder_id' => $folderId,
            'focus' => $focusPayload['focus'],
            'focus_terms' => $focusPayload['focus_terms'],
            'area' => $validated['area'] ?? null,
            'answer_type' => $answerType,
            'expected_answer' => $answerType === 'direct_input' ? ($validated['expected_answer'] ?? null) : null,
            'expected_answer_explanation' => $validated['expected_answer_explanation'] ?? null,
            'expected_answer_image_path' => $expectedAnswerImagePath,
            'grade_level' => $gradeLevel,
            'time_limit_seconds' => (int) ($validated['time_limit_seconds'] ?? 180),
            'support_type' => $this->resolveSupportType($supportTitle, $supportText, $imagePath),
            'support_title' => $supportTitle,
            'support_text' => $supportText,
            'support_image_path' => $imagePath,
            'prompt' => $validated['prompt'],
            'require_command_highlight' => $requireCommandHighlight,
            'require_clue_highlight' => $requireClueHighlight,
            'expected_commands' => $isPortuguese ? ($validated['expected_commands'] ?? null) : null,
            'command_feedback' => $isPortuguese ? ($validated['command_feedback'] ?? null) : null,
            'expected_clues' => $isPortuguese ? ($validated['expected_clues'] ?? null) : null,
            'clue_feedback' => $isPortuguese ? ($validated['clue_feedback'] ?? null) : null,
            'correct_option' => $answerType === 'direct_input' ? null : ($validated['correct_option'] ?? null),
        ]);

        if ($answerType === 'direct_input') {
            $pergunta->options()->delete();
        } elseif (! empty($validated['options'])) {
            foreach ($validated['options'] as $opt) {
                QuestionOption::updateOrCreate(
                    ['question_id' => $pergunta->id, 'letter' => $opt['letter']],
                    ['text' => $opt['text'], 'feedback' => $opt['feedback'] ?? null]
                );
            }
        }

        $broadcastKeys = Classroom::where('is_active', true)
            ->whereHas('assessment.questions', fn ($q) => $q->where('questions.id', $pergunta->id))
            ->pluck('broadcast_key')
            ->filter()
            ->values()
            ->all();

        if (! empty($broadcastKeys)) {
            $pergunta->load('options');
            BroadcastService::dispatch(new QuestionUpdated($pergunta, $broadcastKeys));
        }

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pergunta atualizada com sucesso!');
    }

    /**
     * Serve a imagem de apoio da pergunta apenas para o professor proprietário,
     * super_admin ou estudante com tentativa ativa em sala cuja avaliação inclui a pergunta.
     */
    public function showSupportImage(Question $question): StreamedResponse
    {
        if (! $this->canViewSupportImage($question) || ! $question->hasImageSupport()) {
            abort(404);
        }

        $path = $question->support_image_path;
        $disk = null;

        if (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if (! $disk) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $path,
            null,
            [
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    /**
     * Serve a imagem da resolução esperada da pergunta.
     */
    public function showExpectedAnswerImage(Question $question): StreamedResponse
    {
        if (! $this->canViewSupportImage($question) || ! $question->hasExpectedAnswerImage()) {
            abort(404);
        }

        $path = $question->expected_answer_image_path;
        $disk = null;

        if (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if (! $disk) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $path,
            null,
            [
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    /**
     * Exclui uma pergunta.
     */
    public function destroy(Question $pergunta): RedirectResponse
    {
        $this->authorizeQuestion($pergunta);

        if ($pergunta->support_image_path) {
            $this->deleteSupportImageFile($pergunta->support_image_path);
        }

        if ($pergunta->expected_answer_image_path) {
            $this->deleteSupportImageFile($pergunta->expected_answer_image_path);
        }

        $pergunta->delete();

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pergunta excluída com sucesso.');
    }

    private function canViewSupportImage(Question $question): bool
    {
        $user = Auth::user();
        if ($user && $user->isActive() && ($user->id === $question->user_id || $user->isSuperAdmin())) {
            return true;
        }

        $attemptId = session('current_attempt_id');
        if (! $attemptId) {
            return false;
        }

        $attempt = StudentAttempt::with('classroom.assessment.questions')->find($attemptId);
        if (! $attempt || ! $attempt->classroom || ! $attempt->classroom->assessment) {
            return false;
        }

        return $attempt->classroom->assessment->questions->contains('id', $question->id);
    }

    private function deleteSupportImageFile(string $imagePath): void
    {
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($imagePath)) {
                Storage::disk($disk)->delete($imagePath);
            }
        }
    }

    private function authorizeQuestion(Question $question): void
    {
        if ($question->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Acesso não autorizado a esta pergunta.');
        }
    }

    /**
     * Valida os dados de entrada de criação e edição de pergunta.
     *
     * @return array<string, mixed>
     */
    private function validateQuestionData(Request $request): array
    {
        $area = mb_strtolower(trim((string) $request->input('area', '')));
        $isMath = str_contains($area, 'matemát') || str_contains($area, 'matemat');
        $answerType = $request->input('answer_type', 'multiple_choice');

        $rules = [
            'focus' => ['nullable', 'string', 'max:1000'],
            'focus_terms' => ['nullable', 'array'],
            'focus_terms.*' => ['string', 'max:255'],
            'folder_id' => ['nullable'],
            'area' => ['nullable', 'string', 'max:100'],
            'answer_type' => ['nullable', 'in:multiple_choice,direct_input'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
            'support_title' => ['nullable', 'string', 'max:255'],
            'support_text' => ['nullable', 'string'],
            'support_image' => ['nullable', 'image', 'max:5120'],
            'remove_support_image' => ['nullable', 'boolean'],
            'prompt' => ['required', 'string'],
            'expected_answer' => ['nullable', 'string'],
            'expected_answer_explanation' => ['nullable', 'string'],
            'expected_answer_image' => ['nullable', 'image', 'max:5120'],
            'remove_expected_answer_image' => ['nullable', 'boolean'],
            'require_command_highlight' => ['nullable', 'boolean'],
            'require_clue_highlight' => ['nullable', 'boolean'],
            'expected_commands' => ['nullable', 'string'],
            'command_feedback' => ['nullable', 'string'],
            'expected_clues' => ['nullable', 'string'],
            'clue_feedback' => ['nullable', 'string'],
        ];

        if ($isMath && $answerType === 'direct_input') {
            $rules['expected_answer'] = ['required', 'string', 'max:2000'];
            $rules['correct_option'] = ['nullable'];
            $rules['options'] = ['nullable', 'array'];
        } else {
            $rules['correct_option'] = ['required', 'in:A,B,C,D,E'];
            $rules['options'] = ['required', 'array', 'size:5'];
            $rules['options.*.letter'] = ['required', 'in:A,B,C,D,E'];
            $rules['options.*.text'] = ['required', 'string'];
            $rules['options.*.feedback'] = ['nullable', 'string'];
        }

        return $request->validate($rules, [
            'prompt.required' => 'Informe o comando / enunciado da pergunta.',
            'expected_answer.required' => 'Cadastre a resposta esperada para esta pergunta de matemática.',
            'expected_answer_image.image' => 'A foto da resolução esperada deve ser uma imagem válida (JPG, PNG, WEBP).',
            'expected_answer_image.max' => 'A foto da resolução esperada deve ter no máximo 5 MB.',
            'support_image.image' => 'O arquivo de apoio deve ser uma imagem válida (JPG, PNG, WEBP).',
            'support_image.max' => 'A imagem de apoio deve ter no máximo 5 MB.',
            'time_limit_seconds.min' => 'O tempo mínimo por questão é de 30 segundos.',
            'time_limit_seconds.max' => 'O tempo máximo por questão é de 3600 segundos (60 minutos).',
            'correct_option.required' => 'Selecione qual alternativa é o gabarito correto.',
            'options.*.text.required' => 'Preencha o texto de todas as 5 alternativas (A até E).',
        ]);
    }

    private function resolveSupportType(?string $supportTitle, ?string $supportText, ?string $imagePath): string
    {
        $hasText = ! empty(trim((string) $supportTitle))
            || ! empty(trim(strip_tags((string) $supportText)));
        $hasImage = ! empty($imagePath);

        if ($hasText && $hasImage) {
            return 'mixed';
        }

        if ($hasImage) {
            return 'image';
        }

        return 'text';
    }

    private function resolveFolderId(mixed $raw): ?int
    {
        if (empty($raw)) {
            return null;
        }

        $rawStr = (string) $raw;
        $folder = QuestionFolder::where(function ($query) {
            $query->where('user_id', Auth::id())
                ->orWhereHas('shares', function ($q) {
                    $q->where('permission', 'edit')
                        ->where(function ($sq) {
                            $sq->where('user_id', Auth::id())
                                ->orWhere('email', mb_strtolower(Auth::user()->email));
                        });
                });
        })->where(function ($query) use ($rawStr) {
            if (ctype_digit($rawStr)) {
                $query->where('id', (int) $rawStr);
            } else {
                $query->where('uuid', $rawStr);
            }
        })->first();

        return $folder?->id;
    }

    /**
     * Resolve os termos de foco e a string consolidada.
     *
     * @param  array<string, mixed>  $validated
     * @return array{focus: ?string, focus_terms: ?array<int, string>}
     */
    private function resolveFocusPayload(Request $request, array $validated): array
    {
        $terms = $request->input('focus_terms');
        if (is_array($terms)) {
            $terms = array_values(array_filter(array_map('trim', $terms)));
        } else {
            $terms = [];
        }

        $singleFocus = isset($validated['focus']) ? trim((string) $validated['focus']) : null;
        if (empty($terms) && ! empty($singleFocus)) {
            $terms = [$singleFocus];
        }

        if (empty($terms)) {
            return [
                'focus' => null,
                'focus_terms' => null,
            ];
        }

        return [
            'focus' => implode(', ', $terms),
            'focus_terms' => $terms,
        ];
    }
}
