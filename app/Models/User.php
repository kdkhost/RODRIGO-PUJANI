<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'phone',
    'document_number',
    'whatsapp',
    'alternate_phone',
    'birth_date',
    'address_zip',
    'address_street',
    'address_number',
    'address_complement',
    'address_district',
    'address_city',
    'address_state',
    'avatar_path',
    'timezone',
    'is_active',
    'password',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'uploaded_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrador');
    }

    public function isAssociatedLawyer(): bool
    {
        return $this->hasRole('Advogado Associado');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function assignedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'assigned_lawyer_id');
    }

    public function primaryLegalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'primary_lawyer_id');
    }

    public function legalTasks(): HasMany
    {
        return $this->hasMany(LegalTask::class, 'assigned_user_id');
    }

    public function legalCaseUpdates(): HasMany
    {
        return $this->hasMany(LegalCaseUpdate::class, 'created_by');
    }
}
