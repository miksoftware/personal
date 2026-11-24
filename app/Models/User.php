<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'linked_client_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the user has administrator privileges.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->isSuperAdmin();
    }

    /**
     * Determine if the user is the SaaS superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' || str_contains(strtolower($this->email ?? ''), 'softwaremik');
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    /**
     * Client record in the owner's account linked to this SaaS user.
     */
    public function linkedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'linked_client_id')->withoutGlobalScope('user_id');
    }

    /**
     * Check whether this SaaS user has a linked client for shared licenses.
     */
    public function hasLinkedClient(): bool
    {
        return !empty($this->linked_client_id);
    }

    public function licenseOverrides(): HasMany
    {
        return $this->hasMany(LicenseUserOverride::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function developments(): HasMany
    {
        return $this->hasMany(Development::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
