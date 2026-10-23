<div>
    <x-admin.page-header title="Users" description="Manage who can sign in and what they can do.">
        <x-slot:actions>
            <x-admin.button icon="plus" :href="route('admin.users.create')" wire:navigate>New user</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email…"
                    class="block w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            </div>
            <x-admin.select wire:model.live="role" :options="$roles" placeholder="All roles" class="sm:w-44" />
            <x-admin.select wire:model.live="userType" :options="$types" placeholder="All user types" class="sm:w-56" />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Role / type</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="hidden px-5 py-3 md:table-cell">Last sign-in</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" wire:loading.class="opacity-60">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{{ $user->initials() }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs font-normal text-slate-400">(you)</span>@endif</p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <x-admin.badge :color="$user->role->value === 'admin' ? 'brand' : ($user->role->value === 'editor' ? 'sky' : 'slate')">{{ $user->role->label() }}</x-admin.badge>
                                <p class="mt-1 text-xs text-slate-500">{{ $user->typeLabel() ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleActive({{ $user->id }})" title="Toggle status" @disabled($user->is(auth()->user()))>
                                    <x-admin.badge :color="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                                </button>
                            </td>
                            <td class="hidden px-5 py-3 text-xs text-slate-500 md:table-cell">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <x-admin.button variant="ghost" size="sm" icon="edit" :href="route('admin.users.edit', $user)" wire:navigate>Edit</x-admin.button>
                                    @unless ($user->is(auth()->user()))
                                        <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="delete({{ $user->id }})" wire:confirm="Delete {{ $user->name }}? This cannot be undone.">Delete</x-admin.button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="users" title="No users found" description="Try a different search or filter." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
        @endif
    </x-admin.card>
</div>
