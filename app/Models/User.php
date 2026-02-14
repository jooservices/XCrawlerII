<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }
    public function favorites(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\Favorite::class);
    }

    public function javNotifications(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\UserLikeNotification::class);
    }

    public function javHistory(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\UserJavHistory::class);
    }

    /**
     * Get the user's watchlist.
     */
    public function watchlist(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\Watchlist::class);
    }

    /**
     * Get the user's watchlist items.
     */
    public function watchlists(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\Watchlist::class);
    }

    /**
     * Get the ratings created by the user.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(\Modules\JAV\Models\Rating::class);
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array<string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a moderator.
     */
    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    /**
     * Get all permissions for the user.
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles->flatMap->permissions->unique('id');
    }
}
