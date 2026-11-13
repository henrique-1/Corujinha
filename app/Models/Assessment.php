<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'academic_period',
    ];

    protected $hidden = [
        'id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'assessment_question')
            ->withPivot(['order', 'points'])
            ->orderByPivot('order')
            ->withTimestamps();
    }

    /**
     * Retorna a pontuação total máxima da avaliação.
     */
    public function totalPoints(): float
    {
        return (float) $this->questions->sum(fn ($q) => (float) ($q->pivot->points ?? 1.0));
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
