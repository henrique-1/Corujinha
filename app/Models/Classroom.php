<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'assessment_id',
        'name',
        'code',
        'broadcast_key',
        'collective_review_enabled',
        'max_review_changes',
        'is_active',
    ];

    protected $hidden = [
        'id',
        'user_id',
        'assessment_id',
        'broadcast_key',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $classroom): void {
            if (empty($classroom->broadcast_key)) {
                $classroom->broadcast_key = Str::random(40);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'collective_review_enabled' => 'boolean',
            'max_review_changes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function studentAttempts(): HasMany
    {
        return $this->hasMany(StudentAttempt::class);
    }

    /**
     * Retorna o número efetivo de questões que o aluno pode alterar na Revisão Coletiva.
     */
    public function effectiveMaxReviewChanges(): int
    {
        $this->loadMissing('assessment.questions');
        $total = max(1, $this->assessment?->questions->count() ?? 1);

        if ($this->max_review_changes === null) {
            return $total;
        }

        return max(1, min($this->max_review_changes, $total));
    }

    /**
     * Fecha definitivamente a sala de aula para novas respostas.
     */
    public function close(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->update(['is_active' => false]);

        return true;
    }

    /**
     * Gera um código de sala legível e único (padrão AVAL-XXXXXX).
     */
    public static function generateUniqueCode(string $prefix = 'AVAL'): string
    {
        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
