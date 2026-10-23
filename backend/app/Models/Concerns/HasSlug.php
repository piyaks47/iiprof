<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** A unique `slug`, generated from the title when empty. */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $base = Str::slug($model->slug ?: $model->title) ?: strtolower(class_basename($model));
            $slug = $base;
            for ($i = 2; static::query()->where('slug', $slug)->when($model->getKey(), fn ($q) => $q->whereKeyNot($model->getKey()))->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $model->slug = $slug;
        });
    }
}
