<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Funnel extends Model
{
    public const SHARE_ROLE_VIEWER = 'viewer';
    public const SHARE_ROLE_EDITOR = 'editor';

    /** @use HasFactory<\Database\Factories\FunnelFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'custom_domain',
        'description',
        'is_active',
        'target_leads',
        'design_settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'design_settings' => 'array',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(FunnelTemplate::class, 'source_funnel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(FunnelStage::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'funnel_user_shares')
            ->withPivot(['role', 'shared_by_user_id'])
            ->withTimestamps();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FunnelSubmission::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    public function sharedRoleFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($this->relationLoaded('sharedUsers')) {
            $sharedUser = $this->sharedUsers->firstWhere('id', $user->id);

            return $sharedUser?->pivot?->role;
        }

        return $this->sharedUsers()
            ->whereKey($user->id)
            ->first()?->pivot?->role;
    }

    public function canView(?User $user): bool
    {
        return $this->isOwnedBy($user) || $this->sharedRoleFor($user) !== null;
    }

    public function canEdit(?User $user): bool
    {
        if ($this->isOwnedBy($user)) {
            return true;
        }

        return $this->sharedRoleFor($user) === self::SHARE_ROLE_EDITOR;
    }

    public function canShare(?User $user): bool
    {
        return $this->canEdit($user);
    }

    public function canManageLeads(?User $user): bool
    {
        return $this->canEdit($user);
    }
}
