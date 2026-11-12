<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return $user->isActive() && ($user->uuid === $id || (string) $user->id === $id);
});

Broadcast::channel('admin.professores', function (User $user): bool {
    return $user->isActive() && $user->isSuperAdmin();
});

Broadcast::channel('professor.{id}', function (User $user, string $id): bool {
    if (! $user->isActive()) {
        return false;
    }

    if ($user->isSuperAdmin()) {
        return true;
    }

    return $user->uuid === $id || (string) $user->id === $id;
});

Broadcast::channel('classroom.monitor.{id}', function (User $user, string $id): bool {
    if (! $user->isActive()) {
        return false;
    }

    $classroom = Classroom::where(function ($query) use ($id) {
        if (ctype_digit($id)) {
            $query->where('id', (int) $id);
        } else {
            $query->where('uuid', $id);
        }
    })->first();

    if (! $classroom) {
        return false;
    }

    return $classroom->user_id === $user->id || $user->isSuperAdmin();
});
