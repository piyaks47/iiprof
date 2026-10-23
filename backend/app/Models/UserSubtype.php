<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** e.g. Lenders → Banks/NBFC, Microfinance; Job Seekers → Fresher, Freelance … */
class UserSubtype extends Model
{
    protected $fillable = ['user_type_id', 'name', 'slug', 'sort_order', 'is_active'];

    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (UserSubtype $subtype) {
            $base = Str::slug($subtype->slug ?: $subtype->name) ?: 'subtype';
            $slug = $base;
            for ($i = 2; static::query()->where('user_type_id', $subtype->user_type_id)->where('slug', $slug)
                ->when($subtype->id, fn ($q) => $q->whereKeyNot($subtype->id))->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $subtype->slug = $slug;
        });
    }

    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
