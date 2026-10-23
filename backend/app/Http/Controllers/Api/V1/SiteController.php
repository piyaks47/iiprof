<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FooterColumn;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    /** Site-wide data: settings, navigation, announcements, footer and social links. */
    public function __invoke(SiteSettings $settings): JsonResponse
    {
        $values = $settings->forPublic();

        $menu = MenuItem::tree();
        if ($menu === []) {
            // No menu built yet: fall back to the pages marked "In menu".
            $menu = Page::query()->published()->inMenu()->get(['id', 'title', 'slug'])
                ->map(fn (Page $page) => ['id' => $page->id, 'label' => $page->title, 'url' => $page->path(), 'new_tab' => false, 'children' => []])
                ->all();
        }

        $socials = collect([
            'facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn',
            'x' => 'X', 'youtube' => 'YouTube', 'whatsapp' => 'WhatsApp',
        ])->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'url' => $values[$key.'_url'] ?? null])
            ->filter(fn ($social) => filled($social['url']))
            ->values();

        return response()->json([
            'data' => [
                'settings' => $values,
                'menu' => $menu,
                'announcements' => Announcement::query()->active()->ordered()->get(['id', 'text', 'url']),
                'footer' => FooterColumn::query()->active()->ordered()
                    ->with(['links' => fn ($q) => $q->active()])
                    ->get()
                    ->map(fn (FooterColumn $column) => [
                        'id' => $column->id,
                        'title' => $column->title,
                        'links' => $column->links->map(fn ($link) => [
                            'id' => $link->id, 'label' => $link->label, 'url' => $link->url, 'new_tab' => $link->new_tab,
                        ])->values(),
                    ])
                    ->filter(fn ($column) => $column['links']->isNotEmpty())
                    ->values(),
                'socials' => $socials,
            ],
        ]);
    }
}
