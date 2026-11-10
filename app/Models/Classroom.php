<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assessment_id',
        'name',
        'code',
        'collective_review_enabled',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'collective_review_enabled' => 'boolean',
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
     * Gera um código de sala legível e único.
     */
    public static function generateUniqueCode(string $prefix = 'AVAL'): string
    {
        do {
            $code = $prefix.'-'.strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
