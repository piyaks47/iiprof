<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\DashboardImage;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active', 'user_type_id', 'user_subtype_id', 'dashboard_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    /** Memoised list of modules this user may open (see availableModules()). */
    protected ?Collection $resolvedModules = null;

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'author_id');
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function userSubtype(): BelongsTo
    {
        return $this->belongsTo(UserSubtype::class);
    }

    /**
     * Active modules for this user: admins get all of them; everyone else gets the modules
     * given to their role plus the modules given to their user type.
     *
     * @return Collection<int, Module>
     */
    public function availableModules(): Collection
    {
        if ($this->resolvedModules !== null) {
            return $this->resolvedModules;
        }

        $modules = Module::query()->active()->ordered()->with('userTypes:id')->get();

        if (! $this->isAdmin()) {
            $typeActive = $this->userType?->is_active ?? false;
            $modules = $modules->filter(fn (Module $module) => $module->isForRole($this->role)
                || ($typeActive && $module->userTypes->contains('id', $this->user_type_id)))->values();
        }

        return $this->resolvedModules = $modules;
    }

    public function canUseModule(Module $module): bool
    {
        return $this->availableModules()->contains('id', $module->id);
    }

    public function flushModules(): void
    {
        $this->resolvedModules = null;
    }

    public function canManageContent(): bool
    {
        return $this->role?->canManageContent() ?? false;
    }

    /** Banner on the dashboard: own choice → user type default → role default. */
    public function dashboardImageUrl(): string
    {
        return DashboardImage::url($this->dashboard_image)
            ?? DashboardImage::url($this->userType?->feature_image)
            ?? DashboardImage::url(DashboardImage::roleDefault($this->role ?? Role::User));
    }

    /** "Lenders · Banks/NBFC" */
    public function typeLabel(): ?string
    {
        if (! $this->userType) {
            return null;
        }

        return $this->userType->name.($this->userSubtype ? ' · '.$this->userSubtype->name : '');
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canAccessAdmin(): bool
    {
        return $this->is_active && ($this->role?->canAccessAdmin() ?? false);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%");
        }));
    }
}
