<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionFolderShare extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'question_folder_id',
        'user_id',
        'email',
        'permission',
        'invitation_token',
    ];

    protected $hidden = [
        'id',
        'question_folder_id',
        'user_id',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(QuestionFolder::class, 'question_folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
