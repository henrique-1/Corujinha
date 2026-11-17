<?php

namespace App\Notifications;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssessmentInviteNewUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Assessment $assessment,
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
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Aplicação' : 'Apenas Aplicação / Visualização';

        return (new MailMessage)
            ->subject("Convite para Acessar a Avaliação {$this->assessment->title} no Corujinha")
            ->greeting('Olá, Professor(a)!')
            ->line("O professor **{$this->inviter->name}** compartilhou a avaliação **\"{$this->assessment->title}\"** com você no **Corujinha**.")
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->line('Para acessar a avaliação e começar a utilizá-la em suas turmas, crie sua senha de acesso:')
            ->action('Aceitar Convite e Acessar Avaliação', $acceptUrl)
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
