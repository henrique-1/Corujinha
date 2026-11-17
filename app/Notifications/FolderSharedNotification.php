<?php

namespace App\Notifications;

use App\Models\QuestionFolder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FolderSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public QuestionFolder $folder,
        public User $inviter,
        public string $permission
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $folderUrl = route('professor.pastas.show', $this->folder);
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Visualização' : 'Apenas Visualização';

        return (new MailMessage)
            ->subject("Pasta Compartilhada: {$this->folder->name} · Corujinha")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O professor **{$this->inviter->name}** compartilhou a pasta de perguntas **\"{$this->folder->name}\"** com você no **Corujinha**.")
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->action('Acessar Pasta no Banco de Perguntas', $folderUrl)
            ->line('Você já pode visualizar o banco de perguntas desta pasta e, caso deseje, cloná-la integralmente para a sua própria conta.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
