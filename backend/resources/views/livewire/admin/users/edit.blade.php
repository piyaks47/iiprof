<div>
    <x-admin.page-header :title="$title" :back="route('admin.users.index')"
        :description="$form->user ? 'Update details, role, user type or password.' : 'Create an account for an admin, editor or user.'" />

    <form wire:submit="save" novalidate class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Account">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input label="Full name" wire:model="form.name" required class="sm:col-span-2" />
                    <x-admin.input label="Email" type="email" wire:model="form.email" required />
                    <x-admin.input label="Phone" type="tel" wire:model="form.phone" />
                    <x-admin.input label="{{ $form->user ? 'New password' : 'Password' }}" type="password" wire:model="form.password"
                        autocomplete="new-password" :hint="$form->user ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
                    <x-admin.input label="Confirm password" type="password" wire:model="form.password_confirmation" autocomplete="new-password" />
                </div>
            </x-admin.card>

            <x-admin.card title="User type" description="Decides which modules the user gets (on top of their role).">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.select label="User type" wire:model.live="form.user_type_id" :options="$types" placeholder="— None —" />
                    <x-admin.select label="Subtype" wire:model="form.user_subtype_id" :options="$subtypes" placeholder="{{ $subtypes ? '— Choose —' : '— No subtypes —' }}" :disabled="! $subtypes" />
                </div>
            </x-admin.card>

            @if ($form->user)
                <x-admin.card title="Dashboard feature image" description="Saved immediately. The user can also change it in their profile.">
                    <livewire:admin.dashboard-image-picker :model="$form->user" :key="'picker-user-'.$form->user->id" />
                </x-admin.card>
            @endif
        </div>

        <div class="space-y-6">
            <x-admin.card title="Access">
                <div class="space-y-5">
                    <x-admin.select label="Role" wire:model.live="form.role" :options="$roles" :disabled="$isSelf" :hint="$roleDescription" />
                    <x-admin.toggle label="Active" description="Inactive accounts cannot sign in." wire:model="form.is_active" :disabled="$isSelf" />
                    @if ($isSelf)
                        <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">You can’t change your own role or deactivate yourself.</p>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Modules this user gets" :description="$modules->count().' of the active modules'">
                @if ($modules->isEmpty())
                    <p class="text-sm text-slate-500">None yet — pick a user type, or give modules to the role in Module access.</p>
                @else
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($modules as $module)
                            <li class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 ring-1 ring-brand-100">
                                <x-admin.icon :name="$module->icon" class="size-3.5" /> {{ $module->name }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>

            <div class="flex justify-end gap-2">
                <x-admin.button variant="secondary" :href="route('admin.users.index')" wire:navigate>Cancel</x-admin.button>
                <x-admin.button type="submit" wire:loading.attr="disabled">{{ $form->user ? 'Save changes' : 'Create user' }}</x-admin.button>
            </div>
        </div>
    </form>
</div>
