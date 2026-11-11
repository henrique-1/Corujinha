<?php

namespace App\Events;

use App\Models\Classroom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClassroomListUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Classroom $classroom, public string $action = 'updated')
    {
        $this->classroom->loadMissing('assessment');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('professor.'.$this->classroom->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'salas.updated';
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
            'classroom' => [
                'id' => $this->classroom->id,
                'name' => $this->classroom->name,
                'code' => $this->classroom->code,
                'assessment_title' => $this->classroom->assessment?->title,
                'collective_review_enabled' => $this->classroom->collective_review_enabled,
                'is_active' => $this->classroom->is_active,
                'student_attempts_count' => $this->classroom->studentAttempts()->count(),
            ],
        ];
    }
}
