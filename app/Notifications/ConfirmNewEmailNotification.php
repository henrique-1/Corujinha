<?php

namespace App\Notifications;

use App\Models\EmailChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmNewEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public EmailChangeRequest $changeRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('profile.email.confirm', ['token' => $this->changeRequest->step2_token]);

        return (new MailMessage)
            ->subject('Confirmação do seu Novo E-mail · Corujinha')
            ->greeting('Olá!')
            ->line('Você solicitou vincular este novo endereço de e-mail à sua conta no **Corujinha / Prepara Avalia+**.')
            ->line('Para finalizar o processo de alteração e confirmar que este e-mail pertence a você, clique no botão de ativação abaixo:')
            ->action('Confirmar e Ativar Novo E-mail', $url)
            ->line('Este link de ativação expira em 24 horas.')
            ->line('Se você não reconhece este pedido, nenhuma ação é necessária.')
            ->salutation('Atenciosamente, Equipe Corujinha');
    }
}
