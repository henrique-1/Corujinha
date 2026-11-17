<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionShare extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'question_id',
        'user_id',
        'email',
        'permission',
        'invitation_token',
    ];

    protected $hidden = [
        'id',
        'question_id',
        'user_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
