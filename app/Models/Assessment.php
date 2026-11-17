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

    public function shares(): HasMany
    {
        return $this->hasMany(AssessmentShare::class);
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

        return $this->shares()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->exists();
    }

    /**
     * Escopo para listar avaliações acessíveis pelo usuário (próprias ou compartilhadas).
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
                });
        });
    }
}
