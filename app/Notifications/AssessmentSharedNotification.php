<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Assessment $assessment,
        public User $inviter,
        public string $permission
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('professor.avaliacoes.index');
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Aplicação' : 'Apenas Aplicação / Visualização';

        return (new MailMessage)
            ->subject("Avaliação Compartilhada: {$this->assessment->title} · Corujinha")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O professor **{$this->inviter->name}** compartilhou a avaliação **\"{$this->assessment->title}\"** com você no **Corujinha**.")
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->action('Ver Avaliações Compartilhadas', $url)
            ->line('Você já pode aplicar esta avaliação com suas turmas ou cloná-la para a sua própria conta.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
