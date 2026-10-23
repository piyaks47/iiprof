<div>
    <x-admin.page-header title="User types" description="Kinds of members (Educators, Lenders, Doctor …) with their subtypes. A user's type decides which modules they get.">
        <x-slot:actions>
            <x-admin.button icon="plus" :href="route('admin.user-types.create')" wire:navigate>New user type</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="border-b border-slate-100 p-4">
            <div class="relative max-w-md">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search user types…"
                    class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">User type</th>
                        <th class="hidden px-5 py-3 lg:table-cell">Subtypes</th>
                        <th class="px-5 py-3 text-center">Users</th>
                        <th class="hidden px-5 py-3 text-center sm:table-cell">Modules</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" wire:loading.class="opacity-60">
                    @forelse ($types as $type)
                        <tr wire:key="type-{{ $type->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-admin.icon :name="$type->icon" /></span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.user-types.edit', $type) }}" wire:navigate class="block truncate font-medium text-slate-900 hover:text-brand-600">{{ $type->name }}</a>
                                        <p class="truncate text-xs text-slate-500">{{ $type->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden max-w-md px-5 py-3 lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($type->subtypes->take(5) as $subtype)
                                        <x-admin.badge>{{ $subtype->name }}</x-admin.badge>
                                    @empty
                                        <span class="text-xs text-slate-400">No subtypes</span>
                                    @endforelse
                                    @if ($type->subtypes->count() > 5)
                                        <span class="text-xs text-slate-500">+{{ $type->subtypes->count() - 5 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('admin.users.index', ['type' => $type->id]) }}" wire:navigate class="font-medium text-slate-700 hover:text-brand-600">{{ $type->users_count }}</a>
                            </td>
                            <td class="hidden px-5 py-3 text-center text-slate-700 sm:table-cell">{{ $type->modules_count }}</td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleActive({{ $type->id }})" title="Toggle status">
                                    <x-admin.badge :color="$type->is_active ? 'green' : 'red'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                                </button>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <x-admin.button variant="ghost" size="sm" icon="edit" :href="route('admin.user-types.edit', $type)" wire:navigate>Edit</x-admin.button>
                                    <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="delete({{ $type->id }})"
                                        wire:confirm="Delete “{{ $type->name }}” and its {{ $type->subtypes->count() }} subtypes? {{ $type->users_count }} user(s) will have no type.">Delete</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-admin.empty-state icon="tag" title="No user types found" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</div>
