<?php

namespace App\Notifications;

use App\Mail\MailIcon;
use App\Models\QuestionFolder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class FolderInviteNewUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public QuestionFolder $folder,
        public User $inviter,
        public string $permission,
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $activationUrl = route('invitation.accept', ['token' => $this->token]);
        $shieldIcon = MailIcon::shieldCheck('#623bae', 16);
        $permissionLabel = $this->permission === 'edit' ? 'Edição e Visualização' : 'Apenas Visualização';

        return (new MailMessage)
            ->subject("Convite para Colaborar na Pasta: {$this->folder->name} · Corujinha")
            ->greeting('Olá!')
            ->line("O professor **{$this->inviter->name}** convidou você para colaborar na pasta de perguntas **\"{$this->folder->name}\"** no sistema **Corujinha**.")
            ->line("Permissão concedida: **{$permissionLabel}**.")
            ->line('Para aceitar o convite e criar sua conta com segurança, clique no botão abaixo:')
            ->action('Finalizar Cadastro e Acessar Pasta', $activationUrl)
            ->line(new HtmlString(
                '<div style="background-color:#f8f6fe; border:1px solid #dfdcf8; border-left:4px solid #623bae; border-radius:10px; padding:12px 16px; margin:20px 0; font-size:13px; color:#24304a; line-height:1.5;">'
                .$shieldIcon.'<strong>Segurança NIST SP 800-63B-4:</strong> Sua senha deve possuir entre 15 e 64 caracteres.'
                .'</div>'
            ))
            ->line('Se você não esperava por este convite, pode desconsiderar esta mensagem.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
