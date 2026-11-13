<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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
     * Retorna os limites (data de início mais antiga e término mais recente) da aplicação da sala.
     *
     * @return array{start: ?Carbon, end: ?Carbon}
     */
    public function applicationDateRange(): array
    {
        if ($this->relationLoaded('studentAttempts')) {
            $attempts = $this->studentAttempts;
            $dates = collect();
            foreach ($attempts as $attempt) {
                if ($attempt->started_at) {
                    $dates->push($attempt->started_at);
                }
                if ($attempt->completed_at) {
                    $dates->push($attempt->completed_at);
                }
            }

            if ($dates->isEmpty()) {
                return ['start' => null, 'end' => null];
            }

            return [
                'start' => $dates->min(),
                'end' => $dates->max(),
            ];
        }

        $minStart = $this->studentAttempts()->whereNotNull('started_at')->min('started_at');
        $maxEnd = $this->studentAttempts()->whereNotNull('completed_at')->max('completed_at')
            ?? $this->studentAttempts()->whereNotNull('started_at')->max('started_at');

        return [
            'start' => $minStart ? Carbon::parse($minStart) : null,
            'end' => $maxEnd ? Carbon::parse($maxEnd) : null,
        ];
    }

    /**
     * Retorna a data de aplicação formatada:
     * - Se não houver tentativas: "Não aplicada"
     * - Se as tentativas foram no mesmo dia: "DD/MM/YYYY"
     * - Se as tentativas foram em dias diferentes: "DD/MM/YYYY até DD/MM/YYYY"
     */
    public function applicationDateFormatted(): string
    {
        $range = $this->applicationDateRange();

        if (! $range['start'] && ! $range['end']) {
            return 'Não aplicada';
        }

        $start = $range['start'] ?? $range['end'];
        $end = $range['end'] ?? $range['start'];

        if ($start->isSameDay($end)) {
            return $start->format('d/m/Y');
        }

        return $start->format('d/m/Y').' até '.$end->format('d/m/Y');
    }

    /**
     * Alfabeto alfanumérico completo (A-Z e 0-9, contendo 0, O, 1, I, L).
     */
    private const ROOM_CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Gera um código de sala legível e único (padrão AVAL-XXXXXX).
     */
    public static function generateUniqueCode(string $prefix = 'AVAL'): string
    {
        $maxIndex = strlen(self::ROOM_CODE_ALPHABET) - 1;

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ROOM_CODE_ALPHABET[random_int(0, $maxIndex)];
            }
            $code = $prefix.'-'.$suffix;
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
