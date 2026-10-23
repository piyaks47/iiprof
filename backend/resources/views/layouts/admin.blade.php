<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#070f20">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ $siteName }} Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    {{-- Collapsed sidebar is applied before the first paint (no layout jump). --}}
    <script>try { if (JSON.parse(localStorage.getItem('admin.rail'))) document.documentElement.setAttribute('data-rail', '') } catch (e) {}</script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $settings = app(\App\Support\SiteSettings::class);
    $userModules = $user->availableModules();
    $currentModule = request()->routeIs('admin.module') ? request()->route('module') : null;
    $currentKey = $currentModule instanceof \App\Models\Module ? $currentModule->key : $currentModule;
    $navGroups = \App\Support\AdminNav::groups($user);
    $crumb = \App\Support\AdminNav::current($navGroups);
    if (! $crumb && $currentKey) {
        $crumb = ['Modules', optional($userModules->firstWhere('key', $currentKey))->name];
    }
    $palette = \App\Support\AdminNav::palette($user, $navGroups, $userModules);
    $shortName = $settings->get('short_name') ?: mb_substr($siteName, 0, 2);
    $logo = $settings->forPublic()['logo_url'] ?? null;
    $unread = $user->can('view-enquiries') ? \App\Models\Enquiry::query()->unread()->count() : 0;
    $roleLabel = $user->isAdmin() ? 'Administrator' : ($user->role === \App\Enums\Role::Editor ? 'Editor' : 'Member');

    // Content groups, then the user's modules, then administration.
    $sections = [];
    foreach (array_diff_key($navGroups, ['Administration' => true]) as $groupName => $items) {
        $sections[] = ['name' => $groupName, 'items' => $items];
    }
    $sections[] = ['name' => 'modules', 'items' => []];
    if (isset($navGroups['Administration'])) {
        $sections[] = ['name' => 'Administration', 'items' => $navGroups['Administration']];
    }
@endphp
<body class="h-full font-sans text-slate-800 antialiased" x-data="adminShell(@js($palette))" x-on:keydown.window="onKey($event)">

    {{-- Mobile backdrop --}}
    <div x-cloak x-show="drawer" x-transition.opacity.duration.300ms class="fixed inset-0 z-40 bg-brand-950/60 backdrop-blur-sm lg:hidden" x-on:click="drawer = false"></div>

    {{-- ============================== Sidebar ============================== --}}
    <aside class="sb fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col overflow-hidden bg-brand-950 text-slate-300 shadow-2xl shadow-black/30 lg:translate-x-0"
        :class="drawer ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" aria-label="Sidebar">
        <div aria-hidden="true" class="lattice pointer-events-none absolute inset-0 opacity-40 [mask-image:linear-gradient(to_bottom,black,transparent_35%)]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-24 -top-24 size-64 rounded-full bg-gold-500/10 blur-3xl"></div>

        {{-- Brand --}}
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="relative flex h-[4.5rem] shrink-0 items-center gap-3 px-5">
            <span class="relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-gold-300 to-gold-600 text-sm font-bold text-brand-950 shadow-lg shadow-black/30">
                {{ mb_strtoupper(mb_substr($shortName, 0, 3)) }}
                @if ($logo)
                    {{-- Falls back to the initials if the logo cannot be loaded. --}}
                    <span class="absolute inset-0 flex items-center justify-center bg-white p-1 ring-1 ring-inset ring-gold-400/40">
                        <img src="{{ $logo }}" alt="" class="max-h-full max-w-full object-contain" onerror="this.parentElement.remove()">
                    </span>
                @endif
            </span>
            <span class="sb-hide-rail min-w-0">
                <span class="block truncate font-display text-[15px] font-semibold leading-tight text-white">{{ $siteName }}</span>
                <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[0.22em] text-gold-300/90">{{ $user->isAdmin() ? 'Admin panel' : ($roleLabel === 'Editor' ? 'Editor panel' : 'Member area') }}</span>
            </span>
        </a>

        {{-- Quick search --}}
        <div class="relative px-3 pb-3">
            <button type="button" x-on:click="openPalette()" x-on:mouseenter="hint($el)" x-on:mouseleave="tip.show = false" data-tip="Search (Ctrl K)"
                class="sb-link group flex h-10 w-full items-center gap-3 rounded-xl bg-white/[0.04] px-3 text-sm text-slate-400 ring-1 ring-inset ring-white/10 transition hover:bg-white/[0.07] hover:text-slate-200">
                <x-admin.icon name="search" class="size-[18px] shrink-0" />
                <span class="sb-hide-rail flex flex-1 items-center justify-between whitespace-nowrap">
                    Search or jump to…
                    <kbd class="rounded-md bg-white/10 px-1.5 py-0.5 font-sans text-[10px] font-semibold text-slate-300">Ctrl K</kbd>
                </span>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="sb-scroll relative flex-1 overflow-y-auto overflow-x-hidden px-3 pb-4" aria-label="Admin"
            x-data="sidebarScroll" x-on:scroll.passive="update()" :data-top="atTop" :data-bottom="atBottom">
            <div class="sb-group space-y-0.5 pb-3">
                <x-admin.nav-link :href="route('admin.dashboard')" icon="dashboard" :active="request()->routeIs('admin.dashboard')">Dashboard</x-admin.nav-link>
            </div>

            @foreach ($sections as $section)
                @php
                    $isModules = $section['name'] === 'modules';
                    $items = $section['items'];
                    if ($isModules && $userModules->isEmpty()) continue;
                    $groupActive = $isModules ? $currentKey !== null : collect($items)->contains('active', true);
                    $groupId = $isModules ? 'modules' : \Illuminate\Support\Str::slug($section['name']);
                    $groupLabel = $isModules ? ($user->isAdmin() ? 'All modules' : 'My modules') : $section['name'];
                    $count = $isModules ? $userModules->count() : count($items);
                @endphp
                <div class="sb-group pb-3" x-data="navGroup('{{ $groupId }}', {{ $groupActive ? 'true' : 'false' }})">
                    <button type="button" x-on:click="toggle()" :aria-expanded="open"
                        class="sb-group-title flex w-full items-center gap-2 rounded-md px-3 pb-1.5 pt-1 text-[10.5px] font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:text-slate-300">
                        <span class="flex-1 text-left">{{ $groupLabel }}</span>
                        <span class="rounded-full bg-white/5 px-1.5 text-[10px] font-medium tracking-normal text-slate-500">{{ $count }}</span>
                        <span class="transition duration-300" :class="open ? '' : '-rotate-90'"><x-admin.icon name="chevron-down" class="size-3.5" /></span>
                    </button>
                    <div x-show="open" x-collapse.duration.300ms class="space-y-0.5">
                        @if ($isModules)
                            @foreach ($userModules as $navModule)
                                <x-admin.nav-link :href="$navModule->url()" :icon="$navModule->icon" :active="$currentKey === $navModule->key">{{ $navModule->name }}</x-admin.nav-link>
                            @endforeach
                        @else
                            @foreach ($items as $item)
                                <x-admin.nav-link :href="$item['url']" :icon="$item['icon']" :active="$item['active']" :badge="$item['badge']">{{ $item['label'] }}</x-admin.nav-link>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Footer: view site + collapse --}}
        <div class="relative space-y-1 border-t border-white/[0.07] p-3">
            <a href="{{ url('/') }}" target="_blank" rel="noopener" x-on:mouseenter="hint($el)" x-on:mouseleave="tip.show = false" data-tip="View website"
                class="sb-link group flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
                <x-admin.icon name="globe" class="size-5 shrink-0 text-slate-400 group-hover:text-gold-300" />
                <span class="sb-hide-rail sb-label flex flex-1 items-center justify-between whitespace-nowrap">View website <x-admin.icon name="external" class="size-4 text-slate-500" /></span>
            </a>
            <button type="button" x-on:click="toggleRail()" x-on:mouseenter="hint($el)" x-on:mouseleave="tip.show = false" data-tip="Expand sidebar (Ctrl \)"
                class="sb-link group hidden h-10 w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white lg:flex">
                <span class="sb-rail-flip transition duration-300"><x-admin.icon name="chevron-double-left" class="size-5 shrink-0" /></span>
                <span class="sb-hide-rail sb-label whitespace-nowrap">Collapse sidebar</span>
            </button>
        </div>
    </aside>

    {{-- Floating label for the collapsed sidebar --}}
    <div x-cloak x-show="tip.show" x-transition:enter="transition duration-150" x-transition:enter-start="opacity-0 -translate-x-1"
        class="pointer-events-none fixed left-[5.75rem] z-[60] -translate-y-1/2 whitespace-nowrap rounded-lg bg-brand-900 px-3 py-1.5 text-xs font-semibold text-white shadow-xl ring-1 ring-gold-400/30"
        :style="`top: ${tip.top}px`" x-text="tip.text"></div>

    {{-- ============================== Page ============================== --}}
    <div class="sb-main flex min-h-full flex-col">
        <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200/70 bg-white/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <button type="button" class="-ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" x-on:click="drawer = true" aria-label="Open menu">
                <x-admin.icon name="menu" class="size-6" />
            </button>

            <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
                <ol class="flex items-center gap-2 text-sm">
                    <li class="hidden sm:block"><a href="{{ route('admin.dashboard') }}" wire:navigate class="text-slate-400 transition hover:text-brand-700"><x-admin.icon name="home" class="size-4" /></a></li>
                    @if ($crumb)
                        <li class="hidden text-slate-300 sm:block">/</li>
                        <li class="hidden text-slate-500 sm:block">{{ $crumb[0] }}</li>
                    @endif
                    <li class="hidden text-slate-300 sm:block">/</li>
                    <li class="truncate font-medium text-slate-900">{{ $title ?? ($crumb[1] ?? 'Dashboard') }}</li>
                </ol>
            </nav>

            <button type="button" x-on:click="openPalette()" class="hidden h-9 items-center gap-2 rounded-full bg-slate-100/80 pl-3 pr-2 text-xs text-slate-500 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-100 hover:text-slate-700 md:flex">
                <x-admin.icon name="search" class="size-4" /> Search
                <kbd class="rounded-md bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold text-slate-500 shadow-sm ring-1 ring-slate-200">Ctrl K</kbd>
            </button>
            <button type="button" x-on:click="openPalette()" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Search"><x-admin.icon name="search" class="size-5" /></button>

            @can('view-enquiries')
                <a href="{{ route('admin.resources.index', 'enquiries') }}" wire:navigate class="relative rounded-full p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700" title="Enquiries">
                    <x-admin.icon name="inbox" class="size-5" />
                    @if ($unread)
                        <span class="absolute right-1 top-1 flex size-4 items-center justify-center rounded-full bg-gold-500 text-[9px] font-bold text-white ring-2 ring-white">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
            @endcan

            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <button type="button" class="flex items-center gap-2.5 rounded-full py-1 pl-1 pr-2 transition hover:bg-slate-100" x-on:click="open = ! open" :aria-expanded="open">
                    <span class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-700 to-brand-950 text-xs font-semibold text-gold-200 ring-2 ring-white">{{ $user->initials() }}</span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-medium leading-tight text-slate-800">{{ $user->name }}</span>
                        <span class="block text-xs leading-tight text-slate-500">{{ $roleLabel }}{{ $user->userType ? ' · '.$user->userType->name : '' }}</span>
                    </span>
                    <x-admin.icon name="chevron-down" class="size-4 text-slate-400" />
                </button>
                <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                    x-transition:leave="transition ease-in duration-100" x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-56 origin-top-right rounded-2xl bg-white p-1.5 shadow-xl shadow-brand-950/10 ring-1 ring-slate-200">
                    <div class="px-3 pb-2 pt-1.5">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <a href="{{ route('admin.profile') }}" wire:navigate class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <x-admin.icon name="user" class="size-4 text-slate-400" /> My profile
                    </a>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <x-admin.icon name="globe" class="size-4 text-slate-400" /> View website
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" id="logout-form">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50">
                            <x-admin.icon name="logout" class="size-4" /> Sign out
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-10">
            <div class="mx-auto max-w-7xl animate-page-in">
                {{ $slot }}
            </div>
        </main>

        <footer class="px-4 py-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
            {{ $siteName }} &middot; Press <kbd class="rounded border border-slate-200 bg-white px-1 font-sans">Ctrl K</kbd> to jump anywhere
        </footer>
    </div>

    {{-- ============================== Command palette ============================== --}}
    <div x-cloak x-show="palette" class="fixed inset-0 z-[70] flex items-start justify-center px-4 pt-[12vh]" role="dialog" aria-modal="true" aria-label="Search">
        <div x-show="palette" x-transition.opacity.duration.200ms class="absolute inset-0 bg-brand-950/50 backdrop-blur-sm" x-on:click="closePalette()"></div>
        <div x-show="palette" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-3 scale-[0.98]"
            class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl shadow-brand-950/30 ring-1 ring-slate-200">
            <div class="flex items-center gap-3 border-b border-slate-100 px-4">
                <x-admin.icon name="search" class="size-5 text-slate-400" />
                <input x-ref="paletteInput" x-model="query" x-on:input="cursor = 0" type="text" placeholder="Search pages, sections, modules, actions…"
                    x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                    class="h-14 flex-1 border-0 bg-transparent px-0 text-[15px] text-slate-900 placeholder:text-slate-400 focus:ring-0" aria-label="Search">
                <kbd class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">Esc</kbd>
            </div>
            <ul x-ref="paletteList" class="scroll-thin flex max-h-[55vh] flex-col overflow-y-auto p-2" role="listbox">
                @foreach ($palette as $i => $entry)
                    <li x-show="pos({{ $i }}) !== -1" :style="{ order: pos({{ $i }}) }" role="option" :aria-selected="ranked[cursor] === {{ $i }}"
                        x-on:click="go({{ $i }})" x-on:mousemove="cursor = pos({{ $i }})"
                        class="group flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm aria-selected:bg-brand-950 aria-selected:text-white">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-aria-selected:bg-white/10 group-aria-selected:text-gold-300">
                            <x-admin.icon :name="$entry['icon']" class="size-4" />
                        </span>
                        <span class="flex-1 truncate font-medium">{{ $entry['label'] }}</span>
                        <span class="text-xs text-slate-400 group-aria-selected:text-slate-400">{{ $entry['group'] }}</span>
                    </li>
                @endforeach
                <li x-show="ranked.length === 0" class="px-3 py-10 text-center text-sm text-slate-500" style="order: 999">No match for “<span x-text="query"></span>”</li>
            </ul>
            <div class="flex items-center gap-4 border-t border-slate-100 bg-slate-50/70 px-4 py-2.5 text-[11px] text-slate-500">
                <span><kbd class="rounded bg-white px-1 ring-1 ring-slate-200">↑</kbd> <kbd class="rounded bg-white px-1 ring-1 ring-slate-200">↓</kbd> move</span>
                <span><kbd class="rounded bg-white px-1 ring-1 ring-slate-200">↵</kbd> open</span>
                <span class="ml-auto"><kbd class="rounded bg-white px-1 ring-1 ring-slate-200">/</kbd> anywhere to search</span>
            </div>
        </div>
    </div>

    <x-admin.toasts />
</body>
</html>
