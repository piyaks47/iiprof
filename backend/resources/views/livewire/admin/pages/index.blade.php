<div>
    <x-admin.page-header title="Pages" description="Content shown on the public website. Pages marked “In menu” appear in the navigation.">
        <x-slot:actions>
            @if ($canManage)
                <x-admin.button icon="plus" :href="route('admin.pages.create')" wire:navigate>New page</x-admin.button>
            @else
                <x-admin.badge color="slate"><x-admin.icon name="eye" class="size-3.5" /> View only</x-admin.badge>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search title or slug…"
                    class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <x-admin.select wire:model.live="kind" :options="['text' => 'Pages with text', 'banner' => 'Banner only (no text yet)']" placeholder="All pages" class="sm:w-56" id="f_kind" />
            <x-admin.select wire:model.live="status" :options="$statuses" placeholder="All statuses" class="sm:w-48" />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="hidden px-5 py-3 md:table-cell">Menu</th>
                        <th class="hidden px-5 py-3 lg:table-cell">Updated</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" wire:loading.class="opacity-60">
                    @forelse ($pages as $page)
                        <tr wire:key="page-{{ $page->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($page->featured_image_url)
                                        <img src="{{ $page->featured_image_url }}" alt="" class="size-10 shrink-0 rounded-lg bg-slate-100 object-cover ring-1 ring-slate-200" onerror="this.style.opacity=0">
                                    @else
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><x-admin.icon name="document" /></span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="block truncate font-medium text-slate-900 hover:text-brand-600">{{ $page->title }}</a>
                                        <p class="truncate text-xs text-slate-500">{{ $page->path() }} @unless ($page->hasContent())<x-admin.badge color="gold" class="ml-1">Banner only</x-admin.badge>@endunless @if ($page->isHome())<x-admin.badge color="brand" class="ml-1">Home</x-admin.badge>@endif</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($canManage)
                                    <button type="button" wire:click="togglePublished({{ $page->id }})" title="Toggle published">
                                        <x-admin.badge :color="$page->status->value === 'published' ? 'green' : 'amber'">{{ $page->status->label() }}</x-admin.badge>
                                    </button>
                                @else
                                    <x-admin.badge :color="$page->status->value === 'published' ? 'green' : 'amber'">{{ $page->status->label() }}</x-admin.badge>
                                @endif
                            </td>
                            <td class="hidden px-5 py-3 text-xs text-slate-500 md:table-cell">{{ $page->show_in_menu ? 'Yes · #'.$page->menu_order : '—' }}</td>
                            <td class="hidden px-5 py-3 text-xs text-slate-500 lg:table-cell">{{ $page->updated_at->diffForHumans() }}<br><span class="text-slate-400">{{ $page->author?->name }}</span></td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    @if ($page->status->value === 'published')
                                        <x-admin.button variant="ghost" size="sm" icon="eye" :href="url($page->path())" target="_blank" title="View on site"><span class="sr-only">View</span></x-admin.button>
                                    @endif
                                    <x-admin.button variant="ghost" size="sm" :icon="$canManage ? 'edit' : 'eye'" :href="route('admin.pages.edit', $page)" wire:navigate>{{ $canManage ? 'Edit' : 'View' }}</x-admin.button>
                                    @unless ($page->isHome() || ! $canManage)
                                        <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="delete({{ $page->id }})" wire:confirm="Delete “{{ $page->title }}”?">Delete</x-admin.button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <x-admin.empty-state title="No pages found" description="Create a page or change the filters.">
                                @if ($canManage)<x-admin.button icon="plus" :href="route('admin.pages.create')" wire:navigate>New page</x-admin.button>@endif
                            </x-admin.empty-state>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pages->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $pages->links() }}</div>
        @endif
    </x-admin.card>
</div>
