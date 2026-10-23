<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Key/value site settings stored in the `settings` table and cached.
 * Shared by the admin panel (Livewire) and the public API (React / React Native).
 */
class SiteSettings
{
    public const CACHE_KEY = 'site.settings.v1';

    /** Settings that hold an uploaded file path on the public disk. */
    public const FILE_KEYS = ['logo', 'default_banner', 'login_logo', 'login_background'];

    /**
     * key => [group, label, default, input type]
     *
     * @return array<string, array{group: string, label: string, default: ?string, type: string, hint?: string}>
     */
    public static function schema(): array
    {
        return [
            'site_name' => ['group' => 'General', 'label' => 'Site name', 'default' => 'iiprof', 'type' => 'text'],
            'short_name' => ['group' => 'General', 'label' => 'Short name', 'default' => null, 'type' => 'text', 'hint' => 'Initials used where space is tight, e.g. IIP.'],
            'tagline' => ['group' => 'General', 'label' => 'Tagline', 'default' => 'Built with Laravel, Livewire and React', 'type' => 'text', 'hint' => 'Shown above the site name in the header.'],
            'register_url' => ['group' => 'General', 'label' => 'Register button link', 'default' => null, 'type' => 'link', 'hint' => 'Leave empty to open the enquiry form as a membership request.'],
            'meta_description' => ['group' => 'General', 'label' => 'Default meta description', 'default' => null, 'type' => 'textarea'],
            'logo' => ['group' => 'General', 'label' => 'Logo', 'default' => null, 'type' => 'image'],
            'default_banner' => ['group' => 'General', 'label' => 'Default page banner', 'default' => null, 'type' => 'image'],
            'login_logo' => ['group' => 'Sign-in page', 'label' => 'Sign-in logo', 'default' => null, 'type' => 'image', 'hint' => 'Large logo on the sign-in page (falls back to the logo). PNG, WebP or SVG, up to 2 MB.'],
            'login_background' => ['group' => 'Sign-in page', 'label' => 'Sign-in background', 'default' => null, 'type' => 'image', 'hint' => 'Photo behind the brand panel of the sign-in page. Up to 6 MB.'],

            'contact_email' => ['group' => 'Contact', 'label' => 'Contact email', 'default' => null, 'type' => 'email'],
            'contact_phone' => ['group' => 'Contact', 'label' => 'Phone', 'default' => null, 'type' => 'text'],
            'address' => ['group' => 'Contact', 'label' => 'Address', 'default' => null, 'type' => 'textarea'],

            'facebook_url' => ['group' => 'Social', 'label' => 'Facebook URL', 'default' => null, 'type' => 'url'],
            'instagram_url' => ['group' => 'Social', 'label' => 'Instagram URL', 'default' => null, 'type' => 'url'],
            'linkedin_url' => ['group' => 'Social', 'label' => 'LinkedIn URL', 'default' => null, 'type' => 'url'],
            'x_url' => ['group' => 'Social', 'label' => 'X (Twitter) URL', 'default' => null, 'type' => 'url'],
            'youtube_url' => ['group' => 'Social', 'label' => 'YouTube URL', 'default' => null, 'type' => 'url'],
            'whatsapp_url' => ['group' => 'Social', 'label' => 'WhatsApp link', 'default' => null, 'type' => 'url', 'hint' => 'e.g. https://wa.me/919800000000'],

            'footer_text' => ['group' => 'Footer', 'label' => 'Footer text', 'default' => null, 'type' => 'textarea'],
        ];
    }

    /** @return array<string, ?string> */
    public function all(): array
    {
        $defaults = array_map(fn (array $field) => $field['default'], static::schema());

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            $stored = []; // table not migrated yet
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string, ?string> $values */
    public function set(array $values): void
    {
        foreach (array_intersect_key($values, static::schema()) as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === '' ? null : $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** Values safe to expose through the public API, with file paths turned into URLs. */
    public function forPublic(): array
    {
        $values = $this->all();

        foreach (self::FILE_KEYS as $key) {
            $values[$key.'_url'] = \App\Support\Media\Files::url($values[$key] ?: null);
            unset($values[$key]);
        }

        return $values;
    }
}
