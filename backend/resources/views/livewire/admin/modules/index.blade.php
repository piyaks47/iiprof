<div>
    <x-admin.page-header title="Module access" description="Feature areas of the platform. A user gets a module when it is given to their role or to their user type. Admins always get everything.">
        <x-slot:actions>
            <x-admin.button icon="plus" :href="route('admin.modules.create')" wire:navigate>New module</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Module</th>
                        <th class="px-5 py-3">Roles</th>
                        <th class="hidden px-5 py-3 md:table-cell">User types</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($modules as $module)
                        <tr wire:key="module-{{ $module->id }}" class="hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-admin.icon :name="$module->icon" /></span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.modules.edit', $module->id) }}" wire:navigate class="block font-medium text-slate-900 hover:text-brand-600">{{ $module->name }}</a>
                                        <p class="max-w-xs truncate text-xs text-slate-500">{{ $module->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($module->roles ?? [] as $role)
                                        <x-admin.badge color="sky">{{ $roleLabels[$role] ?? $role }}</x-admin.badge>
                                    @empty
                                        <span class="text-xs text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="hidden max-w-md px-5 py-3 md:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($module->userTypes->take(4) as $type)
                                        <x-admin.badge>{{ $type->name }}</x-admin.badge>
                                    @empty
                                        <span class="text-xs text-slate-400">—</span>
                                    @endforelse
                                    @if ($module->userTypes->count() > 4)
                                        <span class="text-xs text-slate-500">+{{ $module->userTypes->count() - 4 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleActive({{ $module->id }})" title="Switch on/off">
                                    <x-admin.badge :color="$module->is_active ? 'green' : 'red'">{{ $module->is_active ? 'On' : 'Off' }}</x-admin.badge>
                                </button>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    @if ($module->is_active)
                                        <x-admin.button variant="ghost" size="sm" icon="eye" :href="$module->url()" wire:navigate title="Open"><span class="sr-only">Open</span></x-admin.button>
                                    @endif
                                    <x-admin.button variant="ghost" size="sm" icon="edit" :href="route('admin.modules.edit', $module->id)" wire:navigate>Edit</x-admin.button>
                                    <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="delete({{ $module->id }})" wire:confirm="Delete the {{ $module->name }} module?">Delete</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="puzzle" title="No modules" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</div>
