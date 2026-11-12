<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttempt extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'classroom_id',
        'student_name',
        'started_at',
        'completed_at',
        'reviewed_at',
        'status',
        'score',
    ];

    protected $hidden = [
        'id',
        'classroom_id',
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
     * Recalcula a pontuação da tentativa, restrita às questões da avaliação da sala e deduplicada por questão.
     * Se a tentativa já foi revisada, considera is_correct_final; caso contrário, is_correct_initial.
     */
    public function recalculateScore(): int
    {
        $this->loadMissing(['classroom.assessment.questions']);
        $allowedQuestionIds = $this->classroom?->assessment?->questions->pluck('id')->all();

        $answers = $this->answers()
            ->when(is_array($allowedQuestionIds), fn ($q) => $q->whereIn('question_id', $allowedQuestionIds))
            ->orderByDesc('id')
            ->get()
            ->unique('question_id');

        $correct = $answers->filter(function (StudentAnswer $ans) {
            if ($this->status === 'reviewed' || $ans->reviewed_answer !== null) {
                return (bool) $ans->is_correct_final;
            }

            return (bool) $ans->is_correct_initial;
        })->count();

        $this->update(['score' => $correct]);

        return $correct;
    }

    /**
     * Retorna a pontuação proporcional/ponderada obtida pelo estudante.
     */
    public function earnedPoints(): float
    {
        $this->loadMissing(['classroom.assessment.questions', 'answers']);
        $assessment = $this->classroom?->assessment;
        if (! $assessment) {
            return (float) $this->score;
        }

        $pointsMap = $assessment->questions->pluck('pivot.points', 'id');

        $validAnswers = $this->answers
            ->filter(fn (StudentAnswer $ans) => $pointsMap->has($ans->question_id))
            ->sortByDesc('id')
            ->unique('question_id');

        return (float) $validAnswers->sum(function (StudentAnswer $ans) use ($pointsMap) {
            $isCorrect = ($this->status === 'reviewed' || $ans->reviewed_answer !== null)
                ? (bool) $ans->is_correct_final
                : (bool) $ans->is_correct_initial;

            if (! $isCorrect) {
                return 0.0;
            }

            return (float) ($pointsMap[$ans->question_id] ?? 1.0);
        });
    }

    /**
     * Retorna o total de pontos possíveis da avaliação vinculada a esta tentativa.
     */
    public function totalPossiblePoints(): float
    {
        $this->loadMissing(['classroom.assessment.questions']);

        return (float) ($this->classroom?->assessment?->totalPoints() ?? $this->answers()->count());
    }
}
