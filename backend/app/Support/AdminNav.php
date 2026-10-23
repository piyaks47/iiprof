<?php

namespace App\Support;

use App\Admin\Registry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Sidebar of the admin panel, built from the access levels of the signed-in user:
 * a link is only listed when the user can at least view that area.
 */
class AdminNav
{
    public const GROUP_ORDER = ['Website', 'Homepage', 'Membership', 'Training', 'News & seminars', 'Inbox', 'Administration'];

    /**
     * @return array<string, list<array{label: string, url: string, icon: string, active: bool, badge: ?int}>>
     */
    public static function groups(User $user): array
    {
        $request = request();
        $currentResource = $request->routeIs('admin.resources.*') ? $request->route('resource') : null;
        $entries = [];

        $entries[] = ['Website', 'Pages', 'admin.pages.index', 'document', 'view-pages', $request->routeIs('admin.pages.*'), null];

        foreach (Registry::all() as $key => $resource) {
            $entries[] = [
                $resource->group(), $resource->label(), ['admin.resources.index', ['resource' => $key]], $resource->icon(),
                'view-'.$resource->area(), $currentResource === $key, $resource,
            ];
        }

        $entries[] = ['Administration', 'Users', 'admin.users.index', 'users', 'manage-users', $request->routeIs('admin.users.*'), null];
        $entries[] = ['Administration', 'User types', 'admin.user-types.index', 'tag', 'manage-user-types', $request->routeIs('admin.user-types.*'), null];
        $entries[] = ['Administration', 'Module access', 'admin.modules.index', 'lock', 'manage-modules', $request->routeIs('admin.modules.*'), null];
        $entries[] = ['Administration', 'Role permissions', 'admin.permissions', 'key', 'manage-permissions', $request->routeIs('admin.permissions'), null];
        $entries[] = ['Administration', 'Settings', 'admin.settings', 'settings', 'view-settings', $request->routeIs('admin.settings'), null];

        $groups = array_fill_keys(self::GROUP_ORDER, []);

        foreach ($entries as [$group, $label, $route, $icon, $ability, $active, $resource]) {
            if (! Gate::forUser($user)->allows($ability)) {
                continue;
            }

            [$name, $params] = is_array($route) ? $route : [$route, []];
            $groups[$group][] = [
                'label' => $label,
                'url' => route($name, $params),
                'icon' => $icon,
                'active' => $active,
                'badge' => $resource?->badge(),
            ];
        }

        return array_filter($groups);
    }

    /** [group, label] of the page being viewed, for the breadcrumb. */
    public static function current(array $groups): ?array
    {
        foreach ($groups as $group => $items) {
            foreach ($items as $item) {
                if ($item['active']) {
                    return [$group, $item['label']];
                }
            }
        }

        return null;
    }

    /**
     * Entries for the Ctrl/⌘K command palette: every page in the sidebar, the user's modules and quick actions.
     *
     * @return list<array{label: string, group: string, url: string, icon: string, external?: bool, action?: string}>
     */
    public static function palette(User $user, array $groups, iterable $modules): array
    {
        $items = [['label' => 'Dashboard', 'group' => 'Go to', 'url' => route('admin.dashboard'), 'icon' => 'dashboard']];

        foreach ($groups as $group => $entries) {
            foreach ($entries as $entry) {
                $items[] = ['label' => $entry['label'], 'group' => $group, 'url' => $entry['url'], 'icon' => $entry['icon']];
            }
        }

        foreach ($modules as $module) {
            $items[] = ['label' => $module->name, 'group' => 'Modules', 'url' => $module->url(), 'icon' => $module->icon];
        }

        $gate = Gate::forUser($user);
        if ($gate->allows('manage-pages')) {
            $items[] = ['label' => 'New page', 'group' => 'Create', 'url' => route('admin.pages.create'), 'icon' => 'plus'];
        }
        foreach (Registry::all() as $key => $resource) {
            if ($resource->canCreate() && $gate->allows('manage-'.$resource->area())) {
                $items[] = ['label' => 'New '.$resource->singular(), 'group' => 'Create', 'url' => route('admin.resources.create', $key), 'icon' => 'plus'];
            }
        }
        if ($gate->allows('manage-users')) {
            $items[] = ['label' => 'New user', 'group' => 'Create', 'url' => route('admin.users.create'), 'icon' => 'plus'];
        }

        $items[] = ['label' => 'My profile', 'group' => 'Account', 'url' => route('admin.profile'), 'icon' => 'user'];
        $items[] = ['label' => 'View website', 'group' => 'Account', 'url' => url('/'), 'icon' => 'globe', 'external' => true];
        $items[] = ['label' => 'Sign out', 'group' => 'Account', 'url' => '#', 'icon' => 'logout', 'action' => 'logout'];

        return $items;
    }
}
