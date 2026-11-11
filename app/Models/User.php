<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'password',
        'role',
        'status',
        'invitation_token',
        'invitation_sent_at',
        'totp_secret',
        'totp_enabled_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
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
}
