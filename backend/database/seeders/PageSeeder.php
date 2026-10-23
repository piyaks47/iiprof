<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Enums\Role;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Starter content only on a fresh install — never re-create pages someone deleted.
        if (Page::query()->exists()) {
            return;
        }

        $authorId = User::query()->where('role', Role::Admin)->value('id');

        $pages = [
            [
                'slug' => Page::HOME_SLUG,
                'title' => 'Welcome to iiprof',
                'menu_order' => 0,
                'excerpt' => 'A fast, modern website managed from a Laravel + Livewire admin panel.',
                'content' => "## Everything in one place\n\nThis home page is managed from the admin panel at **/admin → Pages**. Edit it, add new pages and choose which ones appear in the menu.\n\n- Content is written in Markdown\n- Pages are served to this React site through the `/api/v1` API\n- The same API will power the mobile app",
            ],
            [
                'slug' => 'about',
                'title' => 'About us',
                'menu_order' => 10,
                'excerpt' => 'Who we are and what we do.',
                'content' => "## Our story\n\nTell visitors who you are. Edit this page in **/admin → Pages → About us**.",
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'menu_order' => 20,
                'excerpt' => 'Get in touch.',
                'content' => "## Get in touch\n\nContact details shown in the footer come from **/admin → Settings**.",
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], [
                ...$page,
                'status' => PageStatus::Published,
                'author_id' => $authorId,
                'show_in_menu' => true,
                'published_at' => now(),
            ]);
        }
    }
}
