<?php

namespace App\Notifications;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuestionInviteNewUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Question $question,
        public User $inviter,
        public string $permission,
        public string $invitationToken
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('convite.aceitar', ['token' => $this->invitationToken]);
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Uso' : 'Apenas Visualização / Uso';

        return (new MailMessage)
            ->subject('Convite para Acessar Questões no Banco Pedagógico Corujinha')
            ->greeting('Olá, Professor(a)!')
            ->line("O professor **{$this->inviter->name}** compartilhou uma questão pedagógica com você no **Corujinha**.")
            ->line('Enunciado: *"'.Str::limit($this->question->prompt, 100).'"*')
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->line('Para acessar a questão e utilizá-la em suas turmas, ative sua conta definindo sua senha:')
            ->action('Aceitar Convite e Acessar Banco', $acceptUrl)
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
