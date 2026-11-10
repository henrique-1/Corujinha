<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuestionController extends Controller
{
    /**
     * Lista as perguntas cadastradas pelo professor.
     */
    public function index(): View
    {
        $questions = Auth::user()->questions()
            ->with('options')
            ->latest()
            ->paginate(10);

        return view('professor.perguntas.index', compact('questions'));
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
        $validated = $request->validate([
            'focus' => ['nullable', 'string', 'max:255'],
            'support_type' => ['required', 'in:text,image'],
            'support_title' => ['nullable', 'string', 'max:255'],
            'support_text' => ['required_if:support_type,text', 'nullable', 'string'],
            'support_image' => ['required_if:support_type,image', 'nullable', 'image', 'max:5120'], // até 5MB
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
            'prompt.required' => 'O comando da questão é obrigatório.',
            'support_text.required_if' => 'Informe o texto de apoio da questão.',
            'support_image.required_if' => 'Envie o arquivo da imagem de apoio.',
            'correct_option.required' => 'Selecione qual alternativa é a correta.',
            'options.size' => 'A questão deve conter exatamente 5 alternativas (A, B, C, D e E).',
        ]);

        $imagePath = null;
        if ($request->hasFile('support_image')) {
            $imagePath = $request->file('support_image')->store('questions/images', 'public');
        }

        $question = Question::create([
            'user_id' => Auth::id(),
            'focus' => $validated['focus'] ?? null,
            'support_type' => $validated['support_type'],
            'support_title' => $validated['support_title'] ?? null,
            'support_text' => $validated['support_text'] ?? null,
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

        $validated = $request->validate([
            'focus' => ['nullable', 'string', 'max:255'],
            'support_type' => ['required', 'in:text,image'],
            'support_title' => ['nullable', 'string', 'max:255'],
            'support_text' => ['nullable', 'string'],
            'support_image' => ['nullable', 'image', 'max:5120'],
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
        ]);

        $imagePath = $pergunta->support_image_path;
        if ($request->hasFile('support_image')) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('support_image')->store('questions/images', 'public');
        }

        $pergunta->update([
            'focus' => $validated['focus'] ?? null,
            'support_type' => $validated['support_type'],
            'support_title' => $validated['support_title'] ?? null,
            'support_text' => $validated['support_text'] ?? null,
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

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pergunta atualizada com sucesso!');
    }

    /**
     * Exclui uma pergunta.
     */
    public function destroy(Question $pergunta): RedirectResponse
    {
        $this->authorizeQuestion($pergunta);

        if ($pergunta->support_image_path && Storage::disk('public')->exists($pergunta->support_image_path)) {
            Storage::disk('public')->delete($pergunta->support_image_path);
        }

        $pergunta->delete();

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pergunta excluída com sucesso.');
    }

    private function authorizeQuestion(Question $question): void
    {
        if ($question->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Acesso não autorizado a esta pergunta.');
        }
    }
}
