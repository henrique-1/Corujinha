<?php

namespace App\Notifications;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuestionSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Question $question,
        public User $inviter,
        public string $permission
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('professor.perguntas.index');
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Uso' : 'Apenas Visualização / Uso';

        return (new MailMessage)
            ->subject('Questão Compartilhada com Você · Corujinha')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O professor **{$this->inviter->name}** compartilhou uma questão pedagógica com você no **Corujinha**.")
            ->line('Enunciado: *"'.Str::limit($this->question->prompt, 100).'"*')
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->action('Ver Questões Compartilhadas', $url)
            ->line('Você já pode utilizá-la em suas avaliações ou cloná-la para o seu próprio banco de questões.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
