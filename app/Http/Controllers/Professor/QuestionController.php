<?php

namespace App\Http\Controllers\Professor;

use App\Events\QuestionUpdated;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudentAttempt;
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
        $query = Auth::user()->questions()
            ->with('options');

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

        $areas = Auth::user()->questions()->whereNotNull('area')->distinct()->pluck('area')->filter()->values();
        $gradeLevels = Auth::user()->questions()->whereNotNull('grade_level')->distinct()->pluck('grade_level')->filter()->values();

        return view('professor.perguntas.index', compact('questions', 'areas', 'gradeLevels'));
    }

    /**
     * Formulário de criação de pergunta.
     */
    public function create(): View
    {
        return view('professor.perguntas.create');
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

        $supportTitle = $validated['support_title'] ?? null;
        $supportText = RichTextSanitizer::sanitize($validated['support_text'] ?? null);

        $question = Question::create([
            'user_id' => Auth::id(),
            'focus' => $validated['focus'] ?? null,
            'area' => $validated['area'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'time_limit_seconds' => (int) ($validated['time_limit_seconds'] ?? 180),
            'support_type' => $this->resolveSupportType($supportTitle, $supportText, $imagePath),
            'support_title' => $supportTitle,
            'support_text' => $supportText,
            'support_image_path' => $imagePath,
            'prompt' => $validated['prompt'],
            'require_command_highlight' => $request->boolean('require_command_highlight', true),
            'require_clue_highlight' => $request->boolean('require_clue_highlight', true),
            'expected_commands' => $validated['expected_commands'] ?? null,
            'command_feedback' => $validated['command_feedback'] ?? null,
            'expected_clues' => $validated['expected_clues'] ?? null,
            'clue_feedback' => $validated['clue_feedback'] ?? null,
            'correct_option' => $validated['correct_option'],
        ]);

        foreach ($validated['options'] as $opt) {
            QuestionOption::create([
                'question_id' => $question->id,
                'letter' => $opt['letter'],
                'text' => $opt['text'],
                'feedback' => $opt['feedback'] ?? null,
            ]);
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

        return view('professor.perguntas.edit', ['question' => $pergunta]);
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

        $supportTitle = $validated['support_title'] ?? null;
        $supportText = RichTextSanitizer::sanitize($validated['support_text'] ?? null);

        $pergunta->update([
            'focus' => $validated['focus'] ?? null,
            'area' => $validated['area'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'time_limit_seconds' => (int) ($validated['time_limit_seconds'] ?? 180),
            'support_type' => $this->resolveSupportType($supportTitle, $supportText, $imagePath),
            'support_title' => $supportTitle,
            'support_text' => $supportText,
            'support_image_path' => $imagePath,
            'prompt' => $validated['prompt'],
            'require_command_highlight' => $request->boolean('require_command_highlight'),
            'require_clue_highlight' => $request->boolean('require_clue_highlight'),
            'expected_commands' => $validated['expected_commands'] ?? null,
            'command_feedback' => $validated['command_feedback'] ?? null,
            'expected_clues' => $validated['expected_clues'] ?? null,
            'clue_feedback' => $validated['clue_feedback'] ?? null,
            'correct_option' => $validated['correct_option'],
        ]);

        foreach ($validated['options'] as $opt) {
            QuestionOption::updateOrCreate(
                ['question_id' => $pergunta->id, 'letter' => $opt['letter']],
                ['text' => $opt['text'], 'feedback' => $opt['feedback'] ?? null]
            );
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
     * Exclui uma pergunta.
     */
    public function destroy(Question $pergunta): RedirectResponse
    {
        $this->authorizeQuestion($pergunta);

        if ($pergunta->support_image_path) {
            $this->deleteSupportImageFile($pergunta->support_image_path);
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
        return $request->validate([
            'focus' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:100'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
            'support_title' => ['nullable', 'string', 'max:255'],
            'support_text' => ['nullable', 'string'],
            'support_image' => ['nullable', 'image', 'max:5120'],
            'remove_support_image' => ['nullable', 'boolean'],
            'prompt' => ['required', 'string'],
            'require_command_highlight' => ['nullable', 'boolean'],
            'require_clue_highlight' => ['nullable', 'boolean'],
            'expected_commands' => ['nullable', 'string'],
            'command_feedback' => ['nullable', 'string'],
            'expected_clues' => ['nullable', 'string'],
            'clue_feedback' => ['nullable', 'string'],
            'correct_option' => ['required', 'in:A,B,C,D,E'],
            'options' => ['required', 'array', 'size:5'],
            'options.*.letter' => ['required', 'in:A,B,C,D,E'],
            'options.*.text' => ['required', 'string'],
            'options.*.feedback' => ['nullable', 'string'],
        ], [
            'prompt.required' => 'Informe o comando / enunciado da pergunta.',
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
}
