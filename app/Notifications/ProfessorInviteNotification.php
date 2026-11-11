<?php

namespace App\Notifications;

use App\Mail\MailIcon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ProfessorInviteNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $activationUrl = route('invitation.accept', ['token' => $this->token]);
        $shieldIcon = MailIcon::shieldCheck('#623bae', 16);

        return (new MailMessage)
            ->subject('Convite de Acesso · Corujinha')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Você foi cadastrado como Professor no sistema **Corujinha**.')
            ->line('Para finalizar o seu cadastro e definir sua senha e CPF com segurança, clique no botão seguro abaixo:')
            ->action('Finalizar Meu Cadastro', $activationUrl)
            ->line(new HtmlString(
                '<div style="background-color:#f8f6fe; border:1px solid #dfdcf8; border-left:4px solid #623bae; border-radius:10px; padding:12px 16px; margin:20px 0; font-size:13px; color:#24304a; line-height:1.5;">'
                .$shieldIcon.'<strong>Segurança NIST SP 800-63B-4:</strong> Sua senha deve possuir entre 15 e 64 caracteres.'
                .'</div>'
            ))
            ->line('Se você não solicitou este acesso, desconsidere este e-mail.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
