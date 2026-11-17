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
        'resolution_image_path',
        'is_correct_initial',
        'is_correct_final',
        'time_spent_seconds',
        'score',
        'is_manually_graded',
        'manual_score',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'initial_markers' => 'array',
            'reviewed_markers' => 'array',
            'is_correct_initial' => 'boolean',
            'is_correct_final' => 'boolean',
            'time_spent_seconds' => 'integer',
            'score' => 'float',
            'is_manually_graded' => 'boolean',
            'manual_score' => 'float',
        ];
    }

    public function hasResolutionImage(): bool
    {
        return ! empty($this->resolution_image_path);
    }

    public function getResolutionImageUrlAttribute(): ?string
    {
        if (! $this->hasResolutionImage()) {
            return null;
        }

        return route('midia.resolucao', ['studentAnswer' => $this->id], false);
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
