<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionShare;
use App\Models\User;
use App\Notifications\QuestionInviteNewUserNotification;
use App\Notifications\QuestionSharedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuestionShareController extends Controller
{
    /**
     * Compartilha uma questão individual com outro professor por e-mail.
     */
    public function share(Request $request, Question $pergunta): RedirectResponse
    {
        $this->authorizeOwner($pergunta);

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
            return back()->withErrors(['email' => 'Você não pode compartilhar a questão com você mesmo.']);
        }

        $targetUser = User::where('email', $email)->first();

        if ($targetUser) {
            QuestionShare::updateOrCreate(
                [
                    'question_id' => $pergunta->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                ]
            );

            $targetUser->notify(new QuestionSharedNotification($pergunta, Auth::user(), $validated['permission']));
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

            QuestionShare::updateOrCreate(
                [
                    'question_id' => $pergunta->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                    'invitation_token' => $token,
                ]
            );

            $targetUser->notify(new QuestionInviteNewUserNotification($pergunta, Auth::user(), $validated['permission'], $token));
        }

        return back()->with('success', "Questão compartilhada com {$email} com sucesso!");
    }

    /**
     * Remove um compartilhamento existente de uma questão.
     */
    public function removeShare(Question $pergunta, QuestionShare $share): RedirectResponse
    {
        $this->authorizeOwner($pergunta);

        if ($share->question_id !== $pergunta->id) {
            abort(403);
        }

        $share->delete();

        return back()->with('success', 'Acesso revogado com sucesso.');
    }

    /**
     * Clona a questão individual para a conta do professor atual.
     */
    public function clone(Question $pergunta): RedirectResponse
    {
        if (! $pergunta->canView(Auth::user())) {
            abort(403);
        }

        $currentUser = Auth::user();

        $cloned = $pergunta->replicate(['uuid', 'created_at', 'updated_at']);
        $cloned->user_id = $currentUser->id;
        $cloned->folder_id = null; // desvincula da pasta original
        $cloned->save();

        foreach ($pergunta->options as $option) {
            $clonedOption = $option->replicate(['uuid', 'question_id', 'created_at', 'updated_at']);
            $clonedOption->question_id = $cloned->id;
            $clonedOption->save();
        }

        return redirect()->route('professor.perguntas.index')
            ->with('success', 'Questão clonada com sucesso para o seu banco pessoal!');
    }

    protected function authorizeOwner(Question $question): void
    {
        if (Auth::user()->isSuperUser()) {
            return;
        }

        if ((int) $question->user_id !== (int) Auth::id()) {
            abort(403, 'Apenas o proprietário da questão pode gerenciar seus compartilhamentos.');
        }
    }
}
