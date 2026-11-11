<?php

namespace App\Notifications;

use App\Mail\MailIcon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends Notification
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
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $clockIcon = MailIcon::clock('#854d0e', 16);
        $shieldIcon = MailIcon::shieldCheck('#623bae', 16);

        return (new MailMessage)
            ->subject('Recuperação de Senha · Corujinha')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no **Corujinha**.')
            ->line('Para escolher uma nova senha, clique no botão seguro abaixo:')
            ->action('Redefinir Minha Senha', $resetUrl)
            ->line(new HtmlString(
                '<div style="background-color:#fffdf0; border:1px solid #fde68a; border-left:4px solid #f59e0b; border-radius:10px; padding:12px 16px; margin:20px 0 10px 0; font-size:13px; color:#78350f; line-height:1.5;">'
                .$clockIcon.'<strong>Atenção:</strong> Este link é seguro e <strong>expira em 30 minutos</strong>.'
                .'</div>'
            ))
            ->line(new HtmlString(
                '<div style="background-color:#f8f6fe; border:1px solid #dfdcf8; border-left:4px solid #623bae; border-radius:10px; padding:12px 16px; margin:10px 0 20px 0; font-size:13px; color:#24304a; line-height:1.5;">'
                .$shieldIcon.'<strong>Segurança NIST SP 800-63B-4:</strong> Sua nova senha deve possuir entre 15 e 64 caracteres.'
                .'</div>'
            ))
            ->line('Se você não solicitou a redefinição de senha, nenhuma ação é necessária e sua senha atual permanecerá segura.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
