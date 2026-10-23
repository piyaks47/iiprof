<div wire:poll.30s.visible>
    <x-admin.dashboard-header :user="$user" subtitle="People, enquiries, registrations and content at a glance." :live="true">
        <x-admin.button size="sm" variant="secondary" icon="plus" :href="route('admin.pages.create')" wire:navigate>New page</x-admin.button>
        <x-admin.button size="sm" icon="plus" :href="route('admin.users.create')" wire:navigate>New user</x-admin.button>
    </x-admin.dashboard-header>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <x-admin.kpi :label="$kpi['label']" :value="$kpi['value']" :icon="$kpi['icon']" :href="$kpi['href'] ?? null" :trend="$kpi['trend'] ?? null" :spark="$kpi['spark'] ?? null" />
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        @include('livewire.admin.dashboard.partials.charts')

        <x-admin.card title="Members by type" description="Users per user type (top 8)." class="lg:col-span-1">
            <x-slot:actions>
                <a href="{{ route('admin.user-types.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-500">Types &rarr;</a>
            </x-slot:actions>
            <x-admin.chart.bars :items="$typeCounts->map(fn ($t) => [$t->name, $t->users_count, route('admin.user-types.edit', $t)])->push(['Without a type', $untyped, null])->all()" fill="bg-brand-600" />
            <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @foreach ($roleCounts as $label => $count)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 text-xs text-slate-600 ring-1 ring-slate-200">{{ $label }} <span class="font-semibold text-slate-900">{{ $count }}</span></span>
                @endforeach
            </div>
        </x-admin.card>

        @include('livewire.admin.dashboard.partials.content')
        @include('livewire.admin.dashboard.partials.upcoming')

        <x-admin.card title="Latest enquiries" :padding="false">
            <x-slot:actions>
                <a href="{{ route('admin.resources.index', 'enquiries') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-500">All &rarr;</a>
            </x-slot:actions>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentEnquiries as $enquiry)
                    <li class="px-5 py-2.5">
                        <a href="{{ route('admin.resources.edit', ['enquiries', $enquiry->id]) }}" wire:navigate class="flex items-center justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $enquiry->subject ?: Str::limit($enquiry->message, 50) }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $enquiry->name }} · {{ $enquiry->created_at->diffForHumans() }}</span>
                            </span>
                            <x-admin.badge :color="$enquiry->status === 'new' ? 'gold' : ($enquiry->status === 'closed' ? 'slate' : 'sky')">{{ \App\Models\Enquiry::STATUSES[$enquiry->status] ?? $enquiry->status }}</x-admin.badge>
                        </a>
                    </li>
                @empty
                    <li><x-admin.empty-state title="No enquiries yet" /></li>
                @endforelse
            </ul>
        </x-admin.card>

        <x-admin.card title="Newest users" :padding="false">
            <x-slot:actions>
                <a href="{{ route('admin.users.index') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-500">All &rarr;</a>
            </x-slot:actions>
            <ul class="divide-y divide-slate-100">
                @foreach ($recentUsers as $u)
                    <li class="flex items-center gap-3 px-5 py-2.5">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600">{{ $u->initials() }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.users.edit', $u) }}" wire:navigate class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $u->name }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $u->typeLabel() ?? 'No user type' }}</p>
                        </div>
                        <x-admin.badge :color="$u->role->value === 'admin' ? 'brand' : ($u->role->value === 'editor' ? 'sky' : 'slate')">{{ $u->role->label() }}</x-admin.badge>
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        <x-admin.card title="Recently updated pages" :padding="false" class="lg:col-span-3">
            <ul class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-5">
                @forelse ($recentPages as $page)
                    <li class="px-5 py-3 sm:border-t sm:border-slate-100 lg:border-l lg:first:border-l-0">
                        <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $page->title }}</a>
                        <p class="text-xs text-slate-500">{{ $page->status->label() }} &middot; {{ $page->updated_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li><x-admin.empty-state title="No pages yet" /></li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
</div>
