<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_attempt_id',
        'question_id',
        'initial_answer',
        'initial_reason',
        'initial_markers',
        'reviewed_answer',
        'review_reflection',
        'reviewed_markers',
        'is_correct_initial',
        'is_correct_final',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'initial_markers' => 'array',
            'reviewed_markers' => 'array',
            'is_correct_initial' => 'boolean',
            'is_correct_final' => 'boolean',
            'time_spent_seconds' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(StudentAttempt::class, 'student_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
