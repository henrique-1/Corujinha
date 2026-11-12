<?php

namespace App\Events;

use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfessorListUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $professor, public string $action = 'updated') {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.professores'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'professores.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'professor' => [
                'id' => $this->professor->uuid,
                'name' => $this->professor->name,
                'email' => $this->professor->email,
                'cpf' => $this->professor->cpf ? CpfRule::format($this->professor->cpf) : 'Pendente de preenchimento',
                'is_active' => $this->professor->isActive(),
                'is_totp_enabled' => $this->professor->isTotpEnabled(),
            ],
        ];
    }
}
