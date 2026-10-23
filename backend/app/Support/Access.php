<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Access levels per role for each admin area, edited in Admin → Role permissions.
 *
 *  - Admins always have full access to everything.
 *  - Editors and users get the level stored for their role (or the default below).
 *  - "View" shows the area read-only, "Manage" allows creating, editing and deleting.
 *
 * Gates: `view-{area}` and `manage-{area}` (see AppServiceProvider). The sidebar only lists
 * areas the signed-in user can view. Modules (Hunar, Khazana …) are assigned separately,
 * by role and user type, in Module access.
 */
class Access
{
    public const NONE = 0;

    public const VIEW = 1;

    public const MANAGE = 2;

    public const CACHE_KEY = 'access.role-permissions.v1';

    /**
     * area => label, sidebar group, description and the default level for editors / users.
     *
     * @var array<string, array{label: string, group: string, description: string, editor: int, user: int}>
     */
    public const AREAS = [
        'pages' => ['label' => 'Pages', 'group' => 'Website', 'description' => 'Content pages of the website.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'menu' => ['label' => 'Menu', 'group' => 'Website', 'description' => 'Main navigation with drop-downs.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'announcements' => ['label' => 'Announcements', 'group' => 'Website', 'description' => 'Scrolling notices in the top bar.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'footer' => ['label' => 'Footer links', 'group' => 'Website', 'description' => 'Footer columns and their links.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'team' => ['label' => 'Management & team', 'group' => 'Website', 'description' => 'People on the Director Council, Governing Body and other management pages.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'testimonials' => ['label' => 'Testimonials', 'group' => 'Website', 'description' => 'Member testimonials with photo and video.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'home-sections' => ['label' => 'Homepage sections', 'group' => 'Homepage', 'description' => 'Order, visibility and texts of the homepage blocks.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'hero-slides' => ['label' => 'Hero slides', 'group' => 'Homepage', 'description' => 'Slides of the homepage slider.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'stats' => ['label' => 'Key figures', 'group' => 'Homepage', 'description' => 'Animated numbers band.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'highlights' => ['label' => 'Highlights', 'group' => 'Homepage', 'description' => 'Key promises band.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'events' => ['label' => 'Events', 'group' => 'Homepage', 'description' => 'Events carousel and event pages.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'gallery' => ['label' => 'Gallery', 'group' => 'Homepage', 'description' => 'Photo gallery.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'home-blocks' => ['label' => 'Homepage blocks', 'group' => 'Homepage', 'description' => 'Feature tiles, impact numbers, registrations & partners, FAQs.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'membership' => ['label' => 'Membership', 'group' => 'Membership', 'description' => 'Membership blocks of the homepage: their headings, categories, benefits, objectives, how to apply, fees, community highlights and the member directory.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'training' => ['label' => 'Training', 'group' => 'Training', 'description' => 'Training blocks and pages: their headings, programmes (with their own pages), scheduled batches, types of training, benefits, faculty and the programmes & initiatives cards.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'corporate-members' => ['label' => 'Corporate members', 'group' => 'Membership', 'description' => 'Companies and firms that are members (logo wall on the homepage).', 'editor' => self::MANAGE, 'user' => self::NONE],
        'institutional-members' => ['label' => 'Institutional members', 'group' => 'Membership', 'description' => 'Universities, colleges, schools and institutes (logos on the homepage).', 'editor' => self::MANAGE, 'user' => self::NONE],
        'news' => ['label' => 'News & blogs', 'group' => 'News & seminars', 'description' => 'News, blog posts and articles (homepage gallery, /news).', 'editor' => self::MANAGE, 'user' => self::NONE],
        'seminars' => ['label' => 'Seminars', 'group' => 'News & seminars', 'description' => 'Seminar banners on the homepage and seminar pages.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'enquiries' => ['label' => 'Enquiries', 'group' => 'Inbox', 'description' => 'Messages sent from the website.', 'editor' => self::MANAGE, 'user' => self::NONE],
        'settings' => ['label' => 'Site settings', 'group' => 'Administration', 'description' => 'Site name, logo, contact details, social links.', 'editor' => self::NONE, 'user' => self::NONE],
    ];

    /** Areas only admins can open; not configurable. */
    public const ADMIN_ONLY = ['users', 'user-types', 'modules', 'permissions'];

    /** @return array<int, string> */
    public static function levels(): array
    {
        return [self::NONE => 'No access', self::VIEW => 'View', self::MANAGE => 'Manage'];
    }

    /** Roles whose access can be configured. */
    public static function configurableRoles(): array
    {
        return [Role::Editor, Role::User];
    }

    /** @return array<string, array<string, int>> role => area => level, including defaults */
    public static function matrix(): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => RolePermission::query()
                ->get(['role', 'area', 'level'])
                ->groupBy('role')
                ->map(fn ($rows) => $rows->pluck('level', 'area')->map(fn ($l) => (int) $l)->all())
                ->all());
        } catch (Throwable) {
            $stored = []; // table not migrated yet
        }

        $matrix = [];
        foreach (self::configurableRoles() as $role) {
            foreach (self::AREAS as $area => $definition) {
                $matrix[$role->value][$area] = $stored[$role->value][$area] ?? $definition[$role->value];
            }
        }

        return $matrix;
    }

    public static function level(User $user, string $area): int
    {
        if ($user->isAdmin()) {
            return self::MANAGE;
        }

        if (in_array($area, self::ADMIN_ONLY, true)) {
            return self::NONE;
        }

        return self::matrix()[$user->role->value][$area] ?? self::NONE;
    }

    public static function canView(User $user, string $area): bool
    {
        return self::level($user, $area) >= self::VIEW;
    }

    public static function canManage(User $user, string $area): bool
    {
        return self::level($user, $area) >= self::MANAGE;
    }

    /** @param array<string, array<string, int|string>> $matrix role => area => level */
    public static function save(array $matrix): void
    {
        foreach (self::configurableRoles() as $role) {
            foreach (array_keys(self::AREAS) as $area) {
                $level = (int) ($matrix[$role->value][$area] ?? self::NONE);
                RolePermission::query()->updateOrCreate(
                    ['role' => $role->value, 'area' => $area],
                    ['level' => max(self::NONE, min(self::MANAGE, $level))],
                );
            }
        }

        Cache::forget(self::CACHE_KEY);
    }
}
