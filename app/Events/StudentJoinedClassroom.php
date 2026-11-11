<?php

namespace App\Events;

use App\Models\StudentAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentJoinedClassroom implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public StudentAttempt $attempt)
    {
        $this->attempt->load('classroom');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('classroom.'.$this->attempt->classroom_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'student.joined';
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
                'id' => $this->attempt->id,
                'student_name' => $this->attempt->student_name,
                'status' => $this->attempt->status,
                'started_at' => $this->attempt->started_at->format('d/m/Y H:i'),
                'score' => $this->attempt->score,
            ],
        ];
    }
}
