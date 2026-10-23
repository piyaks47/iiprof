<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use App\Models\Module;
use App\Models\Stat;
use App\Models\UserSubtype;
use App\Models\UserType;
use Illuminate\Database\Seeder;

/**
 * Website content: the iiprof.com import (App\Support\IiprofContent, once per installation)
 * plus the key figures. Edits made in the admin panel are never overwritten by a later deploy.
 */
class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        // The iiprof.com content (menus, pages, people, images) — once per installation.
        foreach (\App\Support\IiprofContent::importOnce() as $block => $result) {
            $this->command?->line("  iiprof.com content · {$block}: {$result}");
        }

        // The original iiprof.com page texts, biographies and testimonial quotes — once, into pages not edited yet.
        $pages = \App\Support\IiprofPages::applyOnce();
        if ($pages) {
            $copied = count(array_filter($pages, fn ($r) => str_starts_with($r, 'copied')));
            $this->command?->line("  iiprof.com page texts: {$copied} pages copied");
        }

        // Featured images of the menu (bundled in public/media/menu) — once.
        if ($count = \App\Support\MenuImages::applyOnce()) {
            $this->command?->line("  Menu images: {$count} items");
        }

        // Only what the import does not cover.
        HomeSection::ensureAll();
        $this->stats();

        // Newer homepage blocks (feature tiles, numbers, membership, programmes, FAQ …) — once.
        if ($blocks = \App\Support\HomeBlocks::seedOnce()) {
            $this->command?->line('  Homepage blocks: '.implode(', ', $blocks));
        }

        // Membership and training blocks (programmes, batches, fees, member directory …) — once.
        if ($blocks = \App\Support\MembershipTraining::seedOnce()) {
            $this->command?->line('  Membership & training: '.implode(', ', $blocks));
        }
    }

    private function stats(): void
    {
        if (Stat::query()->exists()) {
            return;
        }

        foreach ([
            [(string) max(UserType::query()->count(), 18), 'Professional communities'],
            [(string) max(UserSubtype::query()->count(), 1).'+', 'Specialisations'],
            [(string) max(Module::query()->count(), 1), 'Integrated platforms'],
            ['24/7', 'Online access for members'],
        ] as [$value, $label]) {
            Stat::query()->create(compact('value', 'label'));
        }
    }
}
