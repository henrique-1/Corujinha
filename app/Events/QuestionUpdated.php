<?php

namespace App\Events;

use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $classroomIds
     */
    public function __construct(public Question $question, public array $classroomIds = [])
    {
        $this->question->loadMissing('options');
    }

    /**
     * Canais públicos das salas de aula que possuem esta questão vinculada.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return collect($this->classroomIds)
            ->unique()
            ->values()
            ->map(fn (int $id): Channel => new Channel('classroom.'.$id))
            ->all();
    }

    /**
     * Nome do evento no broadcast.
     */
    public function broadcastAs(): string
    {
        return 'question.updated';
    }

    /**
     * Dados atualizados da questão para o player do estudante.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'question' => $this->question->toPlayerPayload(),
        ];
    }
}
