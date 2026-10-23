<div>
    <x-admin.page-header :title="$title" :back="route('admin.user-types.index')"
        :description="$userType ? $memberCount.' user(s) have this type.' : 'Add a kind of member, its subtypes and the modules it gets.'" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <form wire:submit="save" novalidate id="type-form">
                <x-admin.card title="Details">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.input label="Name" wire:model="name" required placeholder="e.g. Lenders" />
                        <x-admin.select label="Icon" wire:model.live="icon" :options="$icons" />
                        <x-admin.textarea label="Description" wire:model="description" rows="2" class="sm:col-span-2" />
                        <x-admin.input label="Sort order" type="number" min="0" wire:model="sort_order" hint="Lower numbers are listed first." />
                        <div class="flex items-end pb-1">
                            <x-admin.toggle label="Active" description="Inactive types give no modules." wire:model="is_active" />
                        </div>
                        @unless ($userType)
                            <x-admin.textarea label="Subtypes (one per line)" wire:model="initialSubtypes" rows="5" class="sm:col-span-2"
                                placeholder="Banks/NBFC&#10;Microfinance&#10;Pvt Equity" hint="You can add, rename, reorder and delete subtypes later." />
                        @endunless
                    </div>
                </x-admin.card>
            </form>

            @if ($userType)
                <x-admin.card title="Subtypes" :description="count($subtypeRows).' subtype(s). Changes save automatically.'" :padding="false">
                    <ul class="divide-y divide-slate-100">
                        @forelse ($subtypeRows as $id => $row)
                            <li wire:key="subtype-{{ $id }}" class="flex items-center gap-3 px-5 py-2.5">
                                <div class="flex flex-col">
                                    <button type="button" wire:click="moveSubtype({{ $id }}, 'up')" class="text-slate-400 hover:text-slate-700 disabled:opacity-30" @disabled($loop->first) aria-label="Move up"><x-admin.icon name="arrow-up" class="size-3.5" /></button>
                                    <button type="button" wire:click="moveSubtype({{ $id }}, 'down')" class="text-slate-400 hover:text-slate-700 disabled:opacity-30" @disabled($loop->last) aria-label="Move down"><x-admin.icon name="arrow-down" class="size-3.5" /></button>
                                </div>
                                <div class="flex-1">
                                    <input type="text" wire:model.live.blur="subtypeRows.{{ $id }}.name" aria-label="Subtype name"
                                        class="block w-full rounded-lg border-transparent bg-transparent px-2 py-1.5 text-sm font-medium text-slate-800 hover:border-slate-200 focus:border-brand-500 focus:bg-white focus:ring-brand-500">
                                    @error("subtypeRows.{$id}.name")<p class="px-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <span class="hidden w-20 text-right text-xs text-slate-400 sm:block">{{ $subtypeUserCounts[$id] ?? 0 }} user(s)</span>
                                <label class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <input type="checkbox" wire:model.live="subtypeRows.{{ $id }}.is_active" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Active
                                </label>
                                <button type="button" wire:click="deleteSubtype({{ $id }})" wire:confirm="Delete “{{ $row['name'] }}”?" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Delete subtype">
                                    <x-admin.icon name="trash" class="size-4" />
                                </button>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-slate-500">No subtypes yet — add the first one below.</li>
                        @endforelse
                    </ul>
                    <form wire:submit="addSubtype" novalidate class="flex gap-2 border-t border-slate-100 p-4">
                        <div class="flex-1"><x-admin.input wire:model="newSubtype" placeholder="New subtype name…" aria-label="New subtype" /></div>
                        <x-admin.button type="submit" icon="plus" class="self-start">Add</x-admin.button>
                    </form>
                </x-admin.card>

                <x-admin.card title="Default dashboard image" description="Shown to members of this type who haven’t picked their own. Saved immediately.">
                    <livewire:admin.dashboard-image-picker :model="$userType" :key="'picker-type-'.$userType->id" />
                </x-admin.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.card title="Modules" description="Members of this type get these modules.">
                <div class="space-y-1">
                    @foreach ($modules as $module)
                        <label wire:key="mod-{{ $module->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                            <input type="checkbox" value="{{ $module->id }}" wire:model="moduleIds" form="type-form" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <x-admin.icon :name="$module->icon" class="size-4 text-slate-400" />
                            <span class="flex-1 text-sm text-slate-700">{{ $module->name }}</span>
                            @unless ($module->is_active)<x-admin.badge color="red">Off</x-admin.badge>@endunless
                        </label>
                    @endforeach
                </div>
                @error('moduleIds.*')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </x-admin.card>

            <div class="flex justify-end gap-2">
                <x-admin.button variant="secondary" :href="route('admin.user-types.index')" wire:navigate>Cancel</x-admin.button>
                <x-admin.button type="submit" form="type-form" wire:loading.attr="disabled" wire:target="save">{{ $userType ? 'Save user type' : 'Create user type' }}</x-admin.button>
            </div>
        </div>
    </div>
</div>
