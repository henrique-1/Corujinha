<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

        return (new MailMessage)
            ->subject('Convite de Acesso · Corujinha AVALIA+')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Você foi cadastrado como Professor no sistema Corujinha AVALIA+.')
            ->line('Para finalizar o seu cadastro e definir sua senha e CPF com segurança, clique no botão abaixo:')
            ->action('Finalizar Meu Cadastro', $activationUrl)
            ->line('Por questões de segurança segundo a norma NIST SP 800-63B-4, sua senha deve ter entre 15 e 64 caracteres.')
            ->line('Se você não solicitou este acesso, desconsidere este e-mail.')
            ->salutation('Atenciosamente, Equipe Corujinha AVALIA+');
    }
}
