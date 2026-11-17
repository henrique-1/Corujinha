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
        'game_mode',
        'collective_review_enabled',
        'max_review_changes',
        'is_active',
        'starts_at',
        'ends_at',
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
        if ($this->isCorujao()) {
            return 0;
        }

        $this->loadMissing('assessment.questions');
        $total = max(1, $this->assessment?->questions->count() ?? 1);

        if ($this->max_review_changes === null) {
            return $total;
        }

        return max(1, min($this->max_review_changes, $total));
    }

    /**
     * Determina se a sala está operando no Modo Corujão (Hardcore).
     */
    public function isCorujao(): bool
    {
        return ($this->game_mode ?? 'corujinha') === 'corujao';
    }

    /**
     * Determina se a sala está operando no Modo Corujinha (Padrão).
     */
    public function isCorujinha(): bool
    {
        return ! $this->isCorujao();
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
     * Determina se a sala está aberta para respostas no momento atual.
     */
    public function isOpenNow(): bool
    {
        return $this->accessAvailability()['available'];
    }

    /**
     * Retorna o status de disponibilidade da sala de aula para os estudantes.
     *
     * @return array{available: bool, reason: string, message: ?string}
     */
    public function accessAvailability(): array
    {
        if (! $this->is_active) {
            return [
                'available' => false,
                'reason' => 'closed_manually',
                'message' => 'Esta sala de aula está fechada para novas respostas.',
            ];
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return [
                'available' => false,
                'reason' => 'not_started',
                'message' => 'Esta sala de aula ainda não foi aberta. Ela estará disponível em '.$this->starts_at->format('d/m/Y \à\s H:i').'.',
            ];
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return [
                'available' => false,
                'reason' => 'expired',
                'message' => 'O prazo para realização desta atividade encerrou em '.$this->ends_at->format('d/m/Y \à\s H:i').'.',
            ];
        }

        return [
            'available' => true,
            'reason' => 'open',
            'message' => null,
        ];
    }

    /**
     * Retorna uma descrição textual do agendamento configurado.
     */
    public function scheduleDescription(): string
    {
        if ($this->starts_at && $this->ends_at) {
            return 'Disponível de '.$this->starts_at->format('d/m/Y H:i').' até '.$this->ends_at->format('d/m/Y H:i');
        }

        if ($this->starts_at) {
            return 'Abre em '.$this->starts_at->format('d/m/Y H:i');
        }

        if ($this->ends_at) {
            return 'Encerra em '.$this->ends_at->format('d/m/Y H:i');
        }

        return 'Sem agendamento (aberta até encerramento manual)';
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
