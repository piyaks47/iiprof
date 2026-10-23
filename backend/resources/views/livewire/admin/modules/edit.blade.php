<div>
    <x-admin.page-header :title="$title" :back="route('admin.modules.index')" description="Name the module and choose who gets it." />

    <form wire:submit="save" novalidate class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Details">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input label="Name" wire:model="name" required placeholder="e.g. Khazana" />
                    <x-admin.input label="Key (URL)" wire:model="key" prefix="/admin/m/" hint="Leave empty to generate from the name." />
                    <x-admin.textarea label="Description" wire:model="description" rows="2" class="sm:col-span-2" />
                    <x-admin.select label="Icon" wire:model.live="icon" :options="$icons" />
                    <x-admin.input label="Sort order" type="number" min="0" wire:model="sort_order" />
                    <div class="sm:col-span-2"><x-admin.toggle label="Active" description="Switched-off modules are hidden from everyone." wire:model="is_active" /></div>
                </div>
            </x-admin.card>

            <x-admin.card title="User types" description="Every member of a ticked user type gets this module." :padding="false">
                <x-slot:actions>
                    <button type="button" wire:click="selectAllTypes(true)" class="text-xs font-semibold text-brand-600 hover:text-brand-500">All</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" wire:click="selectAllTypes(false)" class="text-xs font-semibold text-slate-500 hover:text-slate-700">None</button>
                </x-slot:actions>
                <div class="grid gap-1 p-3 sm:grid-cols-2">
                    @foreach ($types as $type)
                        <label wire:key="type-{{ $type->id }}" class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                            <input type="checkbox" value="{{ $type->id }}" wire:model="typeIds" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <x-admin.icon :name="$type->icon" class="size-4 text-slate-400" />
                            <span class="flex-1 text-sm text-slate-700">{{ $type->name }}</span>
                            <span class="text-xs text-slate-400">{{ $type->users_count }}</span>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Roles" description="Everyone with a ticked role gets this module, whatever their type.">
                <div class="space-y-2">
                    @foreach ($roleOptions as $role)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                            <input type="checkbox" value="{{ $role['value'] }}" wire:model="roles" class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-slate-700">{{ $role['label'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $role['value'] === 'admin' ? 'Admins always see every active module.' : $role['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-admin.card>

            <div class="flex justify-end gap-2">
                <x-admin.button variant="secondary" :href="route('admin.modules.index')" wire:navigate>Cancel</x-admin.button>
                <x-admin.button type="submit" wire:loading.attr="disabled">{{ $module ? 'Save module' : 'Create module' }}</x-admin.button>
            </div>
        </div>
    </form>
</div>
