<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentShare;
use App\Models\User;
use App\Notifications\AssessmentInviteNewUserNotification;
use App\Notifications\AssessmentSharedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssessmentShareController extends Controller
{
    /**
     * Compartilha uma avaliação com outro professor por e-mail.
     */
    public function share(Request $request, Assessment $avaliacao): RedirectResponse
    {
        $this->authorizeOwner($avaliacao);

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
            return back()->withErrors(['email' => 'Você não pode compartilhar a avaliação com você mesmo.']);
        }

        $targetUser = User::where('email', $email)->first();

        if ($targetUser) {
            AssessmentShare::updateOrCreate(
                [
                    'assessment_id' => $avaliacao->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                ]
            );

            $targetUser->notify(new AssessmentSharedNotification($avaliacao, Auth::user(), $validated['permission']));
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

            AssessmentShare::updateOrCreate(
                [
                    'assessment_id' => $avaliacao->id,
                    'email' => $email,
                ],
                [
                    'user_id' => $targetUser->id,
                    'permission' => $validated['permission'],
                    'invitation_token' => $token,
                ]
            );

            $targetUser->notify(new AssessmentInviteNewUserNotification($avaliacao, Auth::user(), $validated['permission'], $token));
        }

        return back()->with('success', "Avaliação compartilhada com {$email} com sucesso!");
    }

    /**
     * Remove um compartilhamento existente.
     */
    public function removeShare(Assessment $avaliacao, AssessmentShare $share): RedirectResponse
    {
        $this->authorizeOwner($avaliacao);

        if ($share->assessment_id !== $avaliacao->id) {
            abort(403);
        }

        $share->delete();

        return back()->with('success', 'Acesso revogado com sucesso.');
    }

    /**
     * Clona integralmente a avaliação e seus vínculos de questões para a conta do professor atual.
     */
    public function clone(Assessment $avaliacao): RedirectResponse
    {
        if (! $avaliacao->canView(Auth::user())) {
            abort(403);
        }

        $currentUser = Auth::user();

        $newAssessment = Assessment::create([
            'user_id' => $currentUser->id,
            'title' => 'Cópia de '.$avaliacao->title,
            'description' => $avaliacao->description,
            'academic_period' => $avaliacao->academic_period,
        ]);

        $originalQuestions = $avaliacao->questions()->withPivot(['order', 'points'])->get();

        foreach ($originalQuestions as $question) {
            $newAssessment->questions()->attach($question->id, [
                'order' => $question->pivot->order,
                'points' => $question->pivot->points,
            ]);
        }

        return redirect()->route('professor.avaliacoes.index')
            ->with('success', "Avaliação clonada com sucesso para a sua conta como \"{$newAssessment->title}\"!");
    }

    protected function authorizeOwner(Assessment $assessment): void
    {
        if (Auth::user()->isSuperUser()) {
            return;
        }

        if ((int) $assessment->user_id !== (int) Auth::id()) {
            abort(403, 'Apenas o proprietário da avaliação pode gerenciar compartilhamentos.');
        }
    }
}
