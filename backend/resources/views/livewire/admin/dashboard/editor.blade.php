<div wire:poll.30s.visible>
    <x-admin.dashboard-header :user="$user" subtitle="Keep the website content fresh." :live="true">
        <x-admin.button size="sm" icon="plus" :href="route('admin.pages.create')" wire:navigate>New page</x-admin.button>
    </x-admin.dashboard-header>

    <div @class(['grid grid-cols-2 gap-3 md:grid-cols-3', 'xl:grid-cols-5' => count($kpis) >= 5])>
        @foreach ($kpis as $kpi)
            <x-admin.kpi :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :href="$kpi['href'] ?? null" :trend="$kpi['trend'] ?? null" :spark="$kpi['spark'] ?? null" />
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        @include('livewire.admin.dashboard.partials.charts')
        @include('livewire.admin.dashboard.partials.content')
        @include('livewire.admin.dashboard.partials.upcoming')

        <x-admin.card title="Recently updated pages" :padding="false" class="lg:col-span-3">
            <x-slot:actions>
                <a href="{{ route('admin.pages.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-500">All &rarr;</a>
            </x-slot:actions>
            <ul class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-3">
                @forelse ($recentPages as $page)
                    <li class="flex items-center gap-3 px-5 py-3 sm:border-t sm:border-slate-100 sm:[&:nth-child(even)]:border-l lg:[&:nth-child(even)]:border-l-0 lg:[&:not(:nth-child(3n+1))]:border-l">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $page->title }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $page->path() }} &middot; {{ $page->author?->name ?? 'Unknown' }}</p>
                        </div>
                        <x-admin.badge :color="$page->status->value === 'published' ? 'green' : 'amber'">{{ $page->status->label() }}</x-admin.badge>
                    </li>
                @empty
                    <li><x-admin.empty-state title="No pages yet" description="Create the first page." /></li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>

    <h2 class="mb-3 mt-6 text-xs font-semibold uppercase tracking-wide text-slate-500">Your modules</h2>
    <x-admin.module-grid :modules="$modules" />
</div>
