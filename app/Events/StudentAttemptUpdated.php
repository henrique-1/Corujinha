<?php

namespace App\Events;

use App\Models\StudentAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentAttemptUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public StudentAttempt $attempt)
    {
        $this->attempt->loadMissing('classroom');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('classroom.monitor.'.$this->attempt->classroom?->uuid),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'student.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'attempt' => [
                'id' => $this->attempt->uuid,
                'status' => $this->attempt->status,
                'score' => $this->attempt->score,
                'is_device_authorized' => (bool) $this->attempt->is_device_authorized,
                'tab_switches_count' => (int) $this->attempt->tab_switches_count,
                'reviewed_at' => $this->attempt->reviewed_at?->format('d/m/Y H:i'),
                'completed_at' => $this->attempt->completed_at?->format('d/m/Y H:i'),
            ],
        ];
    }
}
