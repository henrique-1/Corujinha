<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_email',
        'new_email',
        'step1_token',
        'step2_token',
        'step1_expires_at',
        'step2_expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'step1_expires_at' => 'datetime',
            'step2_expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isStep1Valid(): bool
    {
        return is_null($this->completed_at)
            && is_null($this->step2_token)
            && $this->step1_expires_at->isFuture();
    }

    public function isStep2Valid(): bool
    {
        return is_null($this->completed_at)
            && ! empty($this->step2_token)
            && ! empty($this->new_email)
            && $this->step2_expires_at?->isFuture();
    }
}
