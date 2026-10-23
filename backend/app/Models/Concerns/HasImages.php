<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Image paths on the public disk — or absolute URLs (content imported from another site
 * until `php artisan iiprof:localize-images` copies the files). Local files are removed
 * when replaced or when the row is deleted. Override imageAttributes() for other columns.
 */
trait HasImages
{
    public static function bootHasImages(): void
    {
        static::updated(function ($model) {
            foreach ($model->imageAttributes() as $attribute) {
                $old = $model->getRawOriginal($attribute);
                if ($old && $model->wasChanged($attribute) && static::ownsFile($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::deleted(function ($model) {
            foreach ($model->imageAttributes() as $attribute) {
                $path = $model->getAttribute($attribute);
                if ($path && static::ownsFile($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    /** @return list<string> */
    public function imageAttributes(): array
    {
        return ['image'];
    }

    /**
     * Uploaded files belong to their row and are deleted with it. Remote URLs and the shared copies of
     * iiprof.com images (imports/, possibly used in several places) are never deleted.
     */
    public static function ownsFile(?string $value): bool
    {
        return \App\Support\Media\Files::owned($value);
    }

    public static function isRemoteImage(?string $value): bool
    {
        return (bool) preg_match('~^https?://~i', (string) $value);
    }

    public function imageUrl(string $attribute = 'image'): ?string
    {
        return \App\Support\Media\Files::url($this->getAttribute($attribute));
    }
}
