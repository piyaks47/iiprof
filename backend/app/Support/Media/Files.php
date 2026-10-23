<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;

/**
 * Rules for the image / file values stored in the content:
 *  - "events/cover.jpg"        a file on the public disk (uploaded in the admin) → /storage/events/cover.jpg
 *  - "/media/menu/khazana.webp" a file shipped with the code in public/ (never deleted, survives every deploy)
 *  - "https://…"                a remote file (e.g. iiprof.com, until it is copied here)
 */
final class Files
{
    /** Public address of a stored value (absolute, like Storage::url()). */
    public static function url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }
        if (self::bundled($value)) {
            return rtrim((string) config('app.url'), '/').$value;
        }

        return Storage::disk('public')->url($value);
    }

    /** A file shipped with the code in public/ (/media/…). */
    public static function bundled(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, '/') && ! str_starts_with($value, '//');
    }

    /**
     * True for an uploaded file that belongs to one row/setting and may be deleted with it.
     * Remote URLs, bundled files and the shared copies of iiprof.com files (imports/) are never deleted.
     */
    public static function owned(?string $value): bool
    {
        return $value !== null && $value !== ''
            && ! preg_match('~^https?://~i', $value)
            && ! self::bundled($value)
            && ! str_starts_with($value, MediaLocalizer::DIR.'/');
    }
}
