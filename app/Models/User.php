<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicUuid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'avatar_path',
        'institution',
        'cpf',
        'password',
        'role',
        'status',
        'invitation_token',
        'invitation_sent_at',
        'totp_secret',
        'totp_enabled_at',
        'totp_last_used_slice',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'totp_secret',
        'totp_last_used_slice',
        'invitation_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'totp_enabled_at' => 'datetime',
            'totp_last_used_slice' => 'integer',
            'totp_secret' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    /**
     * Verifica se o usuário é super administrador.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Alias para isSuperAdmin.
     */
    public function isSuperUser(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Verifica se o usuário é professor.
     */
    public function isProfessor(): bool
    {
        return $this->role === 'professor';
    }

    /**
     * Verifica se a conta está ativa.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verifica se o TOTP está ativado para esta conta.
     */
    public function isTotpEnabled(): bool
    {
        return ! empty($this->totp_secret) && $this->totp_enabled_at !== null;
    }

    /**
     * Credenciais Passkey associadas ao usuário.
     */
    public function webauthnCredentials(): HasMany
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    /**
     * Perguntas criadas pelo usuário (professor).
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Avaliações criadas pelo usuário (professor).
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Envia a notificação personalizada de redefinição de senha com validade de 30 minutos.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Salas de aula criadas pelo usuário (professor).
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /**
     * Áreas de atuação/disciplinas lecionadas pelo professor.
     */
    public function teachingAreas(): BelongsToMany
    {
        return $this->belongsToMany(TeachingArea::class, 'teaching_area_user')
            ->withTimestamps();
    }

    /**
     * Pastas de perguntas criadas pelo professor.
     */
    public function questionFolders(): HasMany
    {
        return $this->hasMany(QuestionFolder::class);
    }

    /**
     * Pastas de perguntas compartilhadas com o professor.
     */
    public function sharedQuestionFolders(): BelongsToMany
    {
        return $this->belongsToMany(QuestionFolder::class, 'question_folder_shares', 'user_id', 'question_folder_id')
            ->withPivot(['permission', 'email', 'invitation_token'])
            ->withTimestamps();
    }

    /**
     * Solicitações de alteração de e-mail do usuário.
     */
    public function emailChangeRequests(): HasMany
    {
        return $this->hasMany(EmailChangeRequest::class);
    }

    /**
     * Retorna a URL pública do avatar ou null se não cadastrado.
     */
    public function avatarUrl(): ?string
    {
        if (empty($this->avatar_path)) {
            return null;
        }

        return asset('storage/'.$this->avatar_path);
    }

    /**
     * Iniciais do nome do usuário para exibição em avatar estilizado.
     */
    public function initials(): string
    {
        $parts = array_values(array_filter(explode(' ', trim((string) $this->name))));
        if (empty($parts)) {
            return mb_strtoupper(mb_substr((string) $this->email, 0, 2));
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1));
    }
}
