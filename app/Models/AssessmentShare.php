<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentShare extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'assessment_id',
        'user_id',
        'email',
        'permission',
        'invitation_token',
    ];

    protected $hidden = [
        'id',
        'assessment_id',
        'user_id',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
