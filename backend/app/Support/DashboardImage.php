<?php

namespace App\Support;

use App\Enums\Role;
use Illuminate\Support\Facades\Storage;

/**
 * Dashboard feature (banner) images.
 * Stored value is either "preset:<key>" (built-in SVG artwork in resources/images/dashboard)
 * or a path on the public disk (an uploaded image).
 */
class DashboardImage
{
    public const PRESETS = [
        'aurora' => 'Aurora',
        'ocean' => 'Ocean',
        'sunrise' => 'Sunrise',
        'forest' => 'Forest',
        'midnight' => 'Midnight',
        'blossom' => 'Blossom',
    ];

    public const ROLE_DEFAULTS = [
        'admin' => 'preset:midnight',
        'editor' => 'preset:sunrise',
        'user' => 'preset:ocean',
    ];

    public static function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'preset:')) {
            $key = substr($value, 7);

            return isset(self::PRESETS[$key]) ? self::presetUrl($key) : null;
        }

        return \App\Support\Media\Files::url($value);
    }

    /** Presets are small SVGs, served inline as data URIs (no extra request, no build step). */
    public static function presetUrl(string $key): string
    {
        static $cache = [];

        return $cache[$key] ??= 'data:image/svg+xml;base64,'
            .base64_encode((string) @file_get_contents(resource_path("images/dashboard/{$key}.svg")));
    }

    public static function roleDefault(Role $role): string
    {
        return self::ROLE_DEFAULTS[$role->value] ?? 'preset:aurora';
    }

    public static function isUpload(?string $value): bool
    {
        return $value !== null && $value !== '' && ! str_starts_with($value, 'preset:');
    }

    /** Remove an uploaded file (presets are never deleted). */
    public static function deleteUpload(?string $value): void
    {
        if (self::isUpload($value)) {
            Storage::disk('public')->delete($value);
        }
    }

    /** @return array<string, string> "preset:key" => label */
    public static function presetOptions(): array
    {
        return collect(self::PRESETS)->mapWithKeys(fn ($label, $key) => ["preset:{$key}" => $label])->all();
    }
}
