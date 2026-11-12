<?php

namespace App\Events;

use App\Models\Classroom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClassroomClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Classroom $classroom) {}

    /**
     * Canal público da sala de aula para notificar alunos conectados.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('classroom.'.$this->classroom->id),
        ];
    }

    /**
     * Nome do evento no broadcast.
     */
    public function broadcastAs(): string
    {
        return 'classroom.closed';
    }

    /**
     * Dados enviados para redirecionar o aluno à tela de devolutiva.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'classroom_id' => $this->classroom->id,
            'code' => $this->classroom->code,
            'redirect_url' => route('estudante.resultado', ['code' => $this->classroom->code], false),
            'message' => 'A avaliação foi finalizada pelo professor. Você será redirecionado para a tela de resultado e devolutiva.',
        ];
    }
}
