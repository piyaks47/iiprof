<div>
    <x-admin.page-header :title="$res->label()" :description="$res->description()">
        <x-slot:actions>
            @if (! $canManage)
                <x-admin.badge color="slate"><x-admin.icon name="eye" class="size-3.5" /> View only</x-admin.badge>
            @endif
            @if ($canManage && $res->canCreate())
                <x-admin.button icon="plus" :href="route('admin.resources.create', ['resource' => $res->key(), 'filter' => $filter ?: null])" wire:navigate>
                    New {{ $res->singular() }}
                </x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        @if ($res->searchable() || $res->filters())
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
                @if ($res->searchable())
                    <div class="relative flex-1">
                        <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search…" aria-label="Search"
                            class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                @else
                    <div class="flex-1"></div>
                @endif
                @foreach ($res->filters() as $column => $filterDef)
                    <x-admin.select wire:model.live="filter.{{ $column }}" :options="$filterDef['options']" :placeholder="$filterDef['label']" class="sm:w-56" :id="'filter_'.$column" />
                @endforeach
            </div>
        @endif

        <ul role="list" class="divide-y divide-slate-100" wire:loading.class="opacity-60" wire:target="move,toggle,delete,search,filter">
            @forelse ($records as $record)
                @php
                    $depth = $res->isTree() ? (int) $record->depth : 1;
                    $image = $res->imageFor($record);
                    $active = $res->toggleable() ? (bool) $record->is_active : true;
                    $editUrl = route('admin.resources.edit', ['resource' => $res->key(), 'id' => $record->getKey()]);
                @endphp
                <li wire:key="row-{{ $record->getKey() }}" @class(['group flex items-center gap-3 px-4 py-3 hover:bg-slate-50/70 sm:px-5', 'bg-slate-50/40' => ! $active])>
                    @if ($showMove && $canManage)
                        <div class="flex shrink-0 flex-col">
                            <button type="button" wire:click="move({{ $record->getKey() }}, 'up')" @disabled(isset($siblingBounds['first'][$record->getKey()]))
                                class="rounded p-0.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 disabled:opacity-25 disabled:hover:bg-transparent" title="Move up" aria-label="Move up">
                                <x-admin.icon name="chevron-up" class="size-4" />
                            </button>
                            <button type="button" wire:click="move({{ $record->getKey() }}, 'down')" @disabled(isset($siblingBounds['last'][$record->getKey()]))
                                class="rounded p-0.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 disabled:opacity-25 disabled:hover:bg-transparent" title="Move down" aria-label="Move down">
                                <x-admin.icon name="chevron-down" class="size-4" />
                            </button>
                        </div>
                    @endif

                    <div class="flex min-w-0 flex-1 items-center gap-3" @if ($depth > 1) style="padding-left: {{ ($depth - 1) * 1.75 }}rem" @endif>
                        @if ($depth > 1)
                            <span class="-ml-4 text-slate-300" aria-hidden="true">└</span>
                        @endif
                        @if ($image)
                            <span class="relative flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                                <x-admin.icon :name="$res->icon()" class="size-5" />
                                <img src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover" onerror="this.remove()">
                            </span>
                        @elseif (in_array($res->key(), ['hero-slides', 'events', 'gallery'], true))
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><x-admin.icon :name="$res->icon()" /></span>
                        @endif
                        <div class="min-w-0">
                            <a href="{{ $editUrl }}" wire:navigate @class(['block truncate text-sm font-medium hover:text-brand-600', 'text-slate-900' => $active, 'text-slate-500' => ! $active])>{{ $res->titleFor($record) }}</a>
                            @if ($sub = $res->subtitleFor($record))
                                <p class="truncate text-xs text-slate-500">{{ $sub }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="hidden shrink-0 items-center gap-1.5 md:flex">
                        @foreach ($res->badgesFor($record) as [$text, $color])
                            <x-admin.badge :color="$color">{{ $text }}</x-admin.badge>
                        @endforeach
                    </div>

                    @if ($res->toggleable())
                        @if ($canManage)
                            <button type="button" wire:click="toggle({{ $record->getKey() }})" role="switch" aria-checked="{{ $active ? 'true' : 'false' }}"
                                title="{{ $active ? 'Shown — click to hide' : 'Hidden — click to show' }}"
                                @class(['relative inline-flex h-5 w-9 shrink-0 rounded-full transition', 'bg-brand-600' => $active, 'bg-slate-300' => ! $active])>
                                <span class="sr-only">Visible</span>
                                <span @class(['absolute top-0.5 size-4 rounded-full bg-white shadow transition', 'left-4.5' => $active, 'left-0.5' => ! $active])></span>
                            </button>
                        @else
                            <x-admin.badge :color="$active ? 'green' : 'slate'">{{ $active ? 'Shown' : 'Hidden' }}</x-admin.badge>
                        @endif
                    @endif

                    <div class="flex shrink-0 items-center gap-0.5">
                        <x-admin.button variant="ghost" size="sm" :icon="$canManage ? 'edit' : 'eye'" :href="$editUrl" wire:navigate>
                            <span class="hidden sm:inline">{{ $canManage ? 'Edit' : 'View' }}</span>
                        </x-admin.button>
                        @if ($canManage && $res->canDelete($record))
                            <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="delete({{ $record->getKey() }})"
                                wire:confirm="Delete “{{ $res->titleFor($record) }}”{{ $res->isTree() ? ' and everything inside it' : '' }}?" title="Delete">
                                <span class="sr-only">Delete</span>
                            </x-admin.button>
                        @endif
                    </div>
                </li>
            @empty
                <li>
                    <x-admin.empty-state :icon="$res->icon()" :title="'No '.strtolower($res->label()).' found'" :description="$search !== '' || $filter ? 'Try another search or filter.' : $res->emptyText()">
                        @if ($canManage && $res->canCreate())
                            <x-admin.button icon="plus" :href="route('admin.resources.create', ['resource' => $res->key(), 'filter' => $filter ?: null])" wire:navigate>New {{ $res->singular() }}</x-admin.button>
                        @endif
                    </x-admin.empty-state>
                </li>
            @endforelse
        </ul>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator && $records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </x-admin.card>
</div>
