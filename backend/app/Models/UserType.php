<?php

namespace App\Models;

use App\Support\DashboardImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A kind of member: Management, Educators, Lenders, Doctor, Philanthropist …
 * The type decides which modules a user gets (see Module::userTypes()).
 */
class UserType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'feature_image', 'sort_order', 'is_active'];

    protected $attributes = ['icon' => 'user', 'sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (UserType $type) {
            $type->slug = static::uniqueSlug($type->slug ?: $type->name, $type->id);
        });

        static::deleted(fn (UserType $type) => DashboardImage::deleteUpload($type->feature_image));
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'type';
        $slug = $base;
        for ($i = 2; static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function subtypes(): HasMany
    {
        return $this->hasMany(UserSubtype::class)->orderBy('sort_order')->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
