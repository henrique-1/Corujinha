<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionFolder extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'color',
        'visibility',
    ];

    protected $hidden = [
        'id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'folder_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(QuestionFolderShare::class, 'question_folder_id');
    }

    public function isOwner(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function canView(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        return $this->shares()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->exists();
    }

    public function canEdit(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        return $this->shares()
            ->where('permission', 'edit')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->exists();
    }

    public function permissionFor(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return 'owner';
        }

        $share = $this->shares()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', mb_strtolower($user->email));
            })
            ->first();

        return $share?->permission;
    }
}
