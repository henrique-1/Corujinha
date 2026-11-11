<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'focus',
        'area',
        'grade_level',
        'time_limit_seconds',
        'support_type',
        'support_title',
        'support_text',
        'support_image_path',
        'prompt',
        'require_command_highlight',
        'require_clue_highlight',
        'expected_commands',
        'command_feedback',
        'expected_clues',
        'clue_feedback',
        'correct_option',
    ];

    protected function casts(): array
    {
        return [
            'require_command_highlight' => 'boolean',
            'require_clue_highlight' => 'boolean',
            'time_limit_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('letter');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(Assessment::class, 'assessment_question')
            ->withPivot(['order', 'points'])
            ->withTimestamps();
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function hasTextSupport(): bool
    {
        return ! empty(trim((string) $this->support_title))
            || ! empty(trim(strip_tags((string) $this->support_text)));
    }

    public function hasImageSupport(): bool
    {
        return ! empty($this->support_image_path);
    }

    public function isImageSupport(): bool
    {
        return $this->hasImageSupport();
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->hasImageSupport() || ! $this->exists) {
            return null;
        }

        return route('midia.questao', [
            'question' => $this->id,
            'v' => $this->updated_at?->timestamp ?? 1,
        ], false);
    }
}
