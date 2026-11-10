<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'focus',
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
            ->withPivot('order')
            ->withTimestamps();
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function isImageSupport(): bool
    {
        return $this->support_type === 'image' && ! empty($this->support_image_path);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->isImageSupport()) {
            return null;
        }

        return Storage::url($this->support_image_path);
    }
}
