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
     * @param  array<int, string>  $broadcastKeys
     */
    public function __construct(public Question $question, public array $broadcastKeys = [])
    {
        $this->question->loadMissing('options');
    }

    /**
     * Canais não enumeráveis das salas de aula ativas que possuem esta questão vinculada.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return collect($this->broadcastKeys)
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->unique()
            ->values()
            ->map(fn (string $key): Channel => new Channel('classroom.player.'.$key))
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
