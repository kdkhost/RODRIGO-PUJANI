<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
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
    'pref_receive_internal_messages',
    'pref_receive_whatsapp_messages',
    'password',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public const PROTECTED_ROOT_USER_ID = 1;

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
            'pref_receive_internal_messages' => 'boolean',
            'pref_receive_whatsapp_messages' => 'boolean',
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

    public function scopeVisibleTo(Builder $query, ?self $viewer): Builder
    {
        $query->whereKeyNot(self::PROTECTED_ROOT_USER_ID);

        if ($viewer?->isSuperAdmin()) {
            return $query;
        }

        return $query->whereDoesntHave('roles', function (Builder $roleQuery): void {
            $roleQuery
                ->where('name', 'Super Admin')
                ->where('guard_name', 'web');
        });
    }

    public function canManageOtherUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isAdministrator();
    }

    public function canViewAllLegalOperations(): bool
    {
        return $this->isSuperAdmin() || $this->isAdministrator();
    }

    public function canBeImpersonatedBy(?self $actor): bool
    {
        if (! $actor || $actor->is($this) || $this->isProtectedRootUser() || ! $this->is_active) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->isAdministrator() && ! $this->isSuperAdmin();
    }

    public function canBeDeletedBy(?self $actor): bool
    {
        if (! $actor || $actor->is($this) || $this->isProtectedRootUser() || $this->isSuperAdmin()) {
            return false;
        }

        return $actor->canManageOtherUsers();
    }

    public function canHaveStatusChangedBy(?self $actor): bool
    {
        if (! $actor || $actor->is($this) || $this->isProtectedRootUser() || $this->isSuperAdmin()) {
            return false;
        }

        return $actor->canManageOtherUsers();
    }

    public function isProtectedRootUser(): bool
    {
        return (int) $this->getKey() === self::PROTECTED_ROOT_USER_ID;
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
