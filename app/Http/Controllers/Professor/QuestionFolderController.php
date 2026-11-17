<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\QuestionFolderShare;
use App\Models\User;
use App\Notifications\FolderInviteNewUserNotification;
use App\Notifications\FolderSharedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuestionFolderController extends Controller
{
    /**
     * Cria uma nova pasta de perguntas.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o nome da pasta.',
        ]);

        $folder = QuestionFolder::create([
            'user_id' => Auth::id(),
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'color' => $validated['color'] ?? '#623bae',
            'visibility' => 'private',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'folder' => [
                    'id' => $folder->id,
                    'uuid' => $folder->uuid,
                    'name' => $folder->name,
                ],
            ]);
        }

        return redirect()->route('professor.pastas.show', $folder)
            ->with('success', 'Pasta criada com sucesso!');
    }

    /**
     * Exibe o conteúdo de uma pasta de perguntas.
     */
    public function show(Request $request, QuestionFolder $pasta): View
    {
        $this->authorizeView($pasta);

        $pasta->load(['user', 'shares.user']);

        $query = $pasta->questions()->with('options');

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('prompt', 'like', $term)
                    ->orWhere('support_title', 'like', $term)
                    ->orWhere('focus', 'like', $term);
            });
        }

        if ($request->filled('area')) {
            $query->where('area', $request->input('area'));
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->input('grade_level'));
        }

        $questions = $query->latest()->paginate(10)->withQueryString();

        $areas = $pasta->questions()->whereNotNull('area')->distinct()->pluck('area')->filter()->values();
        $gradeLevels = $pasta->questions()->whereNotNull('grade_level')->distinct()->pluck('grade_level')->filter()->values();

        $isOwner = $pasta->isOwner(Auth::user());
        $canEdit = $pasta->canEdit(Auth::user());

        $availableQuestions = $canEdit
            ? Auth::user()->questions()
                ->where(function ($q) use ($pasta) {
                    $q->whereNull('folder_id')
                        ->orWhere('folder_id', '!=', $pasta->id);
                })
                ->with('folder')
                ->latest()
                ->get()
            : collect();

        return view('professor.perguntas.folder-show', compact(
            'pasta',
            'questions',
            'areas',
            'gradeLevels',
            'isOwner',
            'canEdit',
            'availableQuestions'
        ));
    }

    /**
     * Atualiza os dados da pasta (nome, descrição, cor).
     */
    public function update(Request $request, QuestionFolder $pasta): RedirectResponse
    {
        $this->authorizeEdit($pasta);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o nome da pasta.',
        ]);

        $pasta->update([
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'color' => $validated['color'] ?? $pasta->color,
        ]);

        return back()->with('success', 'Dados da pasta atualizados com sucesso!');
    }

    /**
     * Exclui a pasta.
     */
    public function destroy(QuestionFolder $pasta): RedirectResponse
    {
        $this->authorizeOwner($pasta);

        $pasta->delete();

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Pasta excluída com sucesso.');
    }

    /**
     * Compartilha a pasta com um professor via e-mail e define permissão (view/edit).
     */
    public function share(Request $request, QuestionFolder $pasta): RedirectResponse
    {
        $this->authorizeOwner($pasta);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'permission' => ['required', 'in:view,edit'],
        ], [
            'email.required' => 'Informe o e-mail do professor para compartilhar.',
            'email.email' => 'Informe um e-mail válido.',
            'permission.in' => 'Permissão selecionada inválida.',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $currentUserEmail = mb_strtolower(Auth::user()->email);

        if ($email === $currentUserEmail) {
            return back()->withErrors(['email' => 'Você não pode compartilhar a pasta com você mesmo.']);
        }

        $targetUser = User::where('email', $email)->first();

        if ($targetUser) {
            $share = QuestionFolderShare::updateOrCreate(
                [
                    'question_folder_id' => $pasta->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                ]
            );

            $targetUser->notify(new FolderSharedNotification($pasta, Auth::user(), $validated['permission']));
        } else {
            $token = Str::random(64);
            $targetUser = User::create([
                'name' => explode('@', $email)[0],
                'email' => $email,
                'role' => 'professor',
                'status' => 'pending',
                'invitation_token' => $token,
                'invitation_sent_at' => now(),
            ]);

            $share = QuestionFolderShare::updateOrCreate(
                [
                    'question_folder_id' => $pasta->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                    'invitation_token' => $token,
                ]
            );

            $targetUser->notify(new FolderInviteNewUserNotification($pasta, Auth::user(), $validated['permission'], $token));
        }

        $pasta->update(['visibility' => 'shared']);

        return back()->with('success', "Pasta compartilhada com {$email} com sucesso!");
    }

    /**
     * Remove um compartilhamento existente.
     */
    public function removeShare(QuestionFolder $pasta, QuestionFolderShare $share): RedirectResponse
    {
        $this->authorizeOwner($pasta);

        if ($share->question_folder_id !== $pasta->id) {
            abort(403);
        }

        $share->delete();

        if ($pasta->shares()->count() === 0) {
            $pasta->update(['visibility' => 'private']);
        }

        return back()->with('success', 'Acesso revogado com sucesso.');
    }

    /**
     * Clona integralmente uma pasta e todas as suas perguntas para a conta do professor atual.
     */
    public function clone(QuestionFolder $pasta): RedirectResponse
    {
        $this->authorizeView($pasta);

        $currentUser = Auth::user();

        $newFolder = QuestionFolder::create([
            'user_id' => $currentUser->id,
            'name' => 'Cópia de '.$pasta->name,
            'description' => $pasta->description,
            'color' => $pasta->color,
            'visibility' => 'private',
        ]);

        $originalQuestions = $pasta->questions()->with('options')->get();

        foreach ($originalQuestions as $question) {
            $clonedQuestion = $question->replicate(['uuid', 'created_at', 'updated_at']);
            $clonedQuestion->user_id = $currentUser->id;
            $clonedQuestion->folder_id = $newFolder->id;
            $clonedQuestion->save();

            foreach ($question->options as $option) {
                $clonedOption = $option->replicate(['created_at', 'updated_at']);
                $clonedOption->question_id = $clonedQuestion->id;
                $clonedOption->save();
            }
        }

        return redirect()->route('professor.pastas.show', $newFolder)
            ->with('success', "Pasta '{$newFolder->name}' clonada com sucesso para sua conta! ({$originalQuestions->count()} perguntas copiadas)");
    }

    /**
     * Adiciona ou move perguntas selecionadas do banco para a pasta.
     */
    public function addQuestions(Request $request, QuestionFolder $pasta): RedirectResponse
    {
        $this->authorizeEdit($pasta);

        $validated = $request->validate([
            'question_uuids' => ['required', 'array', 'min:1'],
            'question_uuids.*' => ['string'],
        ], [
            'question_uuids.required' => 'Selecione pelo menos uma pergunta para adicionar à pasta.',
            'question_uuids.min' => 'Selecione pelo menos uma pergunta para adicionar à pasta.',
        ]);

        $user = Auth::user();

        $updatedCount = Question::where('user_id', $user->id)
            ->whereIn('uuid', $validated['question_uuids'])
            ->update(['folder_id' => $pasta->id]);

        if ($updatedCount === 0) {
            return back()->with('error', 'Nenhuma pergunta válida foi encontrada para mover.');
        }

        $message = $updatedCount === 1
            ? '1 pergunta foi adicionada a esta pasta com sucesso!'
            : "{$updatedCount} perguntas foram adicionadas a esta pasta com sucesso!";

        return back()->with('success', $message);
    }

    /**
     * Remove uma pergunta da pasta (retornando-a à raiz do banco de perguntas).
     */
    public function removeQuestion(QuestionFolder $pasta, Question $question): RedirectResponse
    {
        $this->authorizeEdit($pasta);

        if ((int) $question->folder_id !== (int) $pasta->id) {
            return back()->with('error', 'A pergunta informada não pertence a esta pasta.');
        }

        $question->update(['folder_id' => null]);

        return back()->with('success', 'Pergunta removida desta pasta com sucesso.');
    }

    private function authorizeView(QuestionFolder $pasta): void
    {
        if (! $pasta->canView(Auth::user()) && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Acesso não autorizado a esta pasta.');
        }
    }

    private function authorizeEdit(QuestionFolder $pasta): void
    {
        if (! $pasta->canEdit(Auth::user()) && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Você não tem permissão para editar esta pasta.');
        }
    }

    private function authorizeOwner(QuestionFolder $pasta): void
    {
        if (! $pasta->isOwner(Auth::user()) && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Apenas o proprietário da pasta pode gerenciar suas configurações e compartilhamentos.');
        }
    }
}
