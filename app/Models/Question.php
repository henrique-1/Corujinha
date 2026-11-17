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
        'folder_id',
        'focus',
        'focus_terms',
        'area',
        'answer_type',
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
        'expected_answer',
        'expected_answer_explanation',
        'expected_answer_image_path',
    ];

    protected $hidden = [
        'id',
        'user_id',
        'support_image_path',
        'expected_answer_image_path',
        'correct_option',
        'expected_answer',
        'expected_answer_explanation',
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
            'focus_terms' => 'array',
        ];
    }

    /**
     * Retorna os termos de Descritor / Foco Pedagógico / Habilidade como array de strings.
     *
     * @return array<int, string>
     */
    public function getFocusTermsAttribute($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        if (! empty($this->attributes['focus'])) {
            $raw = $this->attributes['focus'];
            if (str_starts_with(trim($raw), '[') && str_ends_with(trim($raw), ']')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }
            }
            $parts = preg_split('/[,•|;]+/', $raw);

            return array_values(array_filter(array_map('trim', (array) $parts)));
        }

        return [];
    }

    /**
     * Define os termos de Descritor / Foco Pedagógico / Habilidade.
     */
    public function setFocusTermsAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['focus_terms'] = null;

            return;
        }

        $terms = is_array($value)
            ? array_values(array_filter(array_map('trim', $value)))
            : array_values(array_filter(array_map('trim', (array) preg_split('/[,•|;]+/', (string) $value))));

        $this->attributes['focus_terms'] = json_encode($terms, JSON_UNESCAPED_UNICODE);
        if (empty($this->attributes['focus']) && ! empty($terms)) {
            $this->attributes['focus'] = implode(', ', $terms);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(QuestionFolder::class, 'folder_id');
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

    public function shares(): HasMany
    {
        return $this->hasMany(QuestionShare::class);
    }

    public function isOwner(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $this->user_id === (int) $user->id;
    }

    public function canEdit(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isOwner($user) || $user->isSuperUser()) {
            return true;
        }

        // Permissão pela pasta pai
        if ($this->folder && $this->folder->canEdit($user)) {
            return true;
        }

        return $this->shares()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->where('permission', 'edit')
            ->exists();
    }

    public function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->canEdit($user)) {
            return true;
        }

        // Permissão pela pasta pai
        if ($this->folder && $this->folder->canView($user)) {
            return true;
        }

        return $this->shares()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->exists();
    }

    /**
     * Escopo para listar perguntas acessíveis pelo usuário (próprias ou compartilhadas).
     */
    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->isSuperUser()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('shares', function ($sq) use ($user) {
                    $sq->where('user_id', $user->id)
                        ->orWhere('email', mb_strtolower($user->email));
                })
                ->orWhereHas('folder.shares', function ($fq) use ($user) {
                    $fq->where('user_id', $user->id)
                        ->orWhere('email', mb_strtolower($user->email));
                });
        });
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

    public function hasExpectedAnswerImage(): bool
    {
        return ! empty($this->expected_answer_image_path);
    }

    public function getExpectedAnswerImageUrlAttribute(): ?string
    {
        if (! $this->hasExpectedAnswerImage() || ! $this->exists) {
            return null;
        }

        return route('midia.questao.resolucao_esperada', [
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

    public function isDirectInput(): bool
    {
        return ($this->answer_type ?? 'multiple_choice') === 'direct_input';
    }

    public function isMultipleChoice(): bool
    {
        return ! $this->isDirectInput();
    }

    public function isMathArea(): bool
    {
        $area = mb_strtolower($this->area ?? '');

        return str_contains($area, 'matemát') || str_contains($area, 'matemat');
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
            'area' => $this->area ?? '',
            'answer_type' => $this->answer_type ?? 'multiple_choice',
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
