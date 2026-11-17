<?php

namespace App\Notifications;

use App\Models\EmailChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmCurrentEmailChangeNotification extends Notification
{
    use Queueable;

    public function __construct(public EmailChangeRequest $changeRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('profile.email.change.form', ['token' => $this->changeRequest->step1_token]);

        return (new MailMessage)
            ->subject('Solicitação de Alteração de E-mail · Corujinha')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Recebemos uma solicitação para alterar o endereço de e-mail associado à sua conta no **Corujinha / Prepara Avalia+**.')
            ->line('Por motivos de segurança (dupla confirmação), para prosseguir e informar o seu novo e-mail, clique no botão abaixo:')
            ->action('Informar Novo E-mail', $url)
            ->line('Este link de confirmação expira em 1 hora.')
            ->line('Se você não solicitou esta alteração, ignore este e-mail. Sua conta permanece segura e o e-mail não será modificado.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
