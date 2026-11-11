<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    /**
     * Lista as avaliações do professor.
     */
    public function index(): View
    {
        $assessments = Auth::user()->assessments()
            ->withCount('questions')
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

        return view('professor.avaliacoes.create', compact('questions'));
    }

    /**
     * Salva a avaliação e associa as perguntas selecionadas com ordem.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['exists:questions,id'],
        ], [
            'title.required' => 'Informe o título da avaliação.',
            'question_ids.required' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
            'question_ids.min' => 'Selecione pelo menos uma pergunta para compor a avaliação.',
        ]);

        $assessment = Assessment::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $syncData = [];
        foreach ($validated['question_ids'] as $index => $qId) {
            $syncData[$qId] = ['order' => $index + 1];
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

        return view('professor.avaliacoes.edit', [
            'assessment' => $avaliacao,
            'questions' => $questions,
            'selectedQuestionIds' => $selectedQuestionIds,
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
            'question_ids.*' => ['exists:questions,id'],
        ]);

        $avaliacao->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $syncData = [];
        foreach ($validated['question_ids'] as $index => $qId) {
            $syncData[$qId] = ['order' => $index + 1];
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

    private function authorizeAssessment(Assessment $assessment): void
    {
        if ($assessment->user_id !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }
}
