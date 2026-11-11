<?php

namespace App\Http\Controllers\Professor;

use App\Events\ClassroomListUpdated;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\StudentAttempt;
use App\Services\BroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    /**
     * Lista as salas de aula do professor.
     */
    public function index(): View
    {
        $classrooms = Auth::user()->classrooms()
            ->with('assessment')
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
        $assessments = Auth::user()->assessments()->has('questions')->latest()->get();
        $suggestedCode = Classroom::generateUniqueCode();

        return view('professor.salas.create', compact('assessments', 'suggestedCode'));
    }

    /**
     * Salva a nova sala de aula.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'assessment_id' => [
                'required',
                Rule::exists('assessments', 'id')->where('user_id', Auth::id()),
            ],
            'code' => ['required', 'string', 'max:20', 'unique:classrooms,code'],
            'collective_review_enabled' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Informe o nome da turma ou sala.',
            'assessment_id.required' => 'Selecione uma avaliação para esta sala.',
            'assessment_id.exists' => 'A avaliação selecionada é inválida ou não pertence à sua conta.',
            'code.required' => 'O código da sala é obrigatório.',
            'code.unique' => 'Este código já está em uso em outra sala.',
        ]);

        $code = strtoupper(trim($validated['code']));

        $classroom = Classroom::create([
            'user_id' => Auth::id(),
            'assessment_id' => $validated['assessment_id'],
            'name' => $validated['name'],
            'code' => $code,
            'collective_review_enabled' => $request->boolean('collective_review_enabled', true),
            'is_active' => true,
        ]);

        BroadcastService::dispatch(new ClassroomListUpdated($classroom, 'created'));

        return redirect()->route('professor.salas.show', $classroom)
            ->with('success', "Sala de Aula criada com sucesso! Código de acesso: {$classroom->code}");
    }

    /**
     * Painel e relatório da sala de aula com os resultados dos alunos.
     */
    public function show(Classroom $sala): View
    {
        $this->authorizeClassroom($sala);

        $sala->load([
            'assessment.questions.options',
            'studentAttempts.answers.question',
        ]);

        $attempts = $sala->studentAttempts()->latest()->paginate(20);

        $totalQuestions = $sala->assessment->questions->count();

        return view('professor.salas.show', [
            'classroom' => $sala,
            'attempts' => $attempts,
            'totalQuestions' => $totalQuestions,
        ]);
    }

    /**
     * Alterna o status da sala (ativa/fechada).
     */
    public function toggleStatus(Classroom $sala): RedirectResponse
    {
        $this->authorizeClassroom($sala);
        $sala->update(['is_active' => ! $sala->is_active]);

        BroadcastService::dispatch(new ClassroomListUpdated($sala, 'updated'));

        $statusMsg = $sala->is_active ? 'aberta para novos alunos.' : 'fechada para respostas.';

        return back()->with('success', "A sala agora está {$statusMsg}");
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
}
