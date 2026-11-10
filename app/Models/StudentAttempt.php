<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'student_name',
        'started_at',
        'completed_at',
        'reviewed_at',
        'status',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    /**
     * Recalcula a pontuação da tentativa.
     * Se a tentativa já foi revisada, considera is_correct_final; caso contrário, is_correct_initial.
     */
    public function recalculateScore(): int
    {
        $answers = $this->answers()->get();

        $correct = $answers->filter(function (StudentAnswer $ans) {
            if ($this->status === 'reviewed' || $ans->reviewed_answer !== null) {
                return (bool) $ans->is_correct_final;
            }

            return (bool) $ans->is_correct_initial;
        })->count();

        $this->update(['score' => $correct]);

        return $correct;
    }
}
