<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A feature area of the platform (Hunar, Jeevika, Khazana, Health …).
 * A user gets a module when the module is given to their role OR to their user type.
 * Admins always get every active module.
 */
class Module extends Model
{
    protected $fillable = ['key', 'name', 'description', 'icon', 'roles', 'sort_order', 'is_active'];

    protected $attributes = ['icon' => 'puzzle', 'sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['roles' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Module $module) {
            $base = Str::slug($module->key ?: $module->name, '_') ?: 'module';
            $key = $base;
            for ($i = 2; static::query()->where('key', $key)->when($module->id, fn ($q) => $q->whereKeyNot($module->id))->exists(); $i++) {
                $key = "{$base}_{$i}";
            }
            $module->key = $key;
            $module->roles = array_values(array_intersect(array_column(Role::cases(), 'value'), $module->roles ?? []));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function userTypes(): BelongsToMany
    {
        return $this->belongsToMany(UserType::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isForRole(Role $role): bool
    {
        return in_array($role->value, $this->roles ?? [], true);
    }

    public function url(): string
    {
        return route('admin.module', $this);
    }
}
