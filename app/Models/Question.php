<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Services\RichTextSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
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

    protected $hidden = [
        'id',
        'user_id',
        'support_image_path',
        'correct_option',
        'expected_commands',
        'command_feedback',
        'expected_clues',
        'clue_feedback',
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
            'question' => $this->uuid,
            'v' => $this->updated_at?->timestamp ?? 1,
        ], false);
    }

    /**
     * Converte os trechos esperados cadastrados pelo professor para o formato de marcadores do TextHighlightService.
     *
     * @return array<int, array{kind: string, text: string}>
     */
    public function expectedHighlightMarkers(): array
    {
        $markers = [];

        foreach (explode(';', (string) ($this->expected_commands ?? '')) as $snippet) {
            $trimmed = trim($snippet);
            if ($trimmed !== '') {
                $markers[] = ['kind' => 'command', 'text' => $trimmed];
            }
        }

        foreach (explode(';', (string) ($this->expected_clues ?? '')) as $snippet) {
            $trimmed = trim($snippet);
            if ($trimmed !== '') {
                $markers[] = ['kind' => 'evidence', 'text' => $trimmed];
            }
        }

        return $markers;
    }

    /**
     * Serializa a questão no formato esperado pelo player interativo do estudante.
     *
     * @return array<string, mixed>
     */
    public function toPlayerPayload(): array
    {
        $this->loadMissing('options');

        return [
            'id' => $this->uuid,
            'focus' => $this->focus ?? '',
            'support_type' => $this->support_type,
            'support_title' => $this->support_title ?? '',
            'support_text' => RichTextSanitizer::toHtml($this->support_text),
            'support_image_url' => $this->image_url,
            'prompt' => $this->prompt,
            'time_limit_seconds' => (int) ($this->time_limit_seconds ?? 180),
            'require_command_highlight' => (bool) $this->require_command_highlight,
            'require_clue_highlight' => (bool) $this->require_clue_highlight,
            'options' => $this->options->map(fn (QuestionOption $opt): array => [
                'letter' => $opt->letter,
                'text' => $opt->text,
            ])->values()->all(),
        ];
    }
}
