<div>
    <x-admin.page-header title="My profile" description="Your details, dashboard image and password." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.card title="Profile">
            <form wire:submit="saveProfile" novalidate class="space-y-5">
                <x-admin.input label="Full name" wire:model="name" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input label="Email" type="email" wire:model="email" required />
                    <x-admin.input label="Phone" type="tel" wire:model="phone" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <span class="block text-sm font-medium text-slate-700">Role</span>
                        <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-slate-200">{{ $user->role->label() }}</p>
                    </div>
                    <div>
                        <span class="block text-sm font-medium text-slate-700">User type</span>
                        <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-slate-200">{{ $user->userType?->name ?? 'Not assigned' }}</p>
                    </div>
                </div>
                @if ($subtypes)
                    <x-admin.select label="Subtype" wire:model="user_subtype_id" :options="$subtypes" placeholder="— Choose —" hint="Which kind of {{ $user->userType->name }} are you?" />
                @endif
                <p class="text-xs text-slate-500">Role and user type are set by an administrator and decide which modules you see.</p>
                <div class="flex justify-end"><x-admin.button type="submit">Save profile</x-admin.button></div>
            </form>
        </x-admin.card>

        <x-admin.card title="Change password">
            <form wire:submit="savePassword" novalidate class="space-y-5">
                <x-admin.input label="Current password" type="password" wire:model="current_password" autocomplete="current-password" />
                <x-admin.input label="New password" type="password" wire:model="password" autocomplete="new-password" />
                <x-admin.input label="Confirm new password" type="password" wire:model="password_confirmation" autocomplete="new-password" />
                <div class="flex justify-end"><x-admin.button type="submit">Change password</x-admin.button></div>
            </form>
        </x-admin.card>

        <x-admin.card id="dashboard-image" title="Dashboard feature image" description="The banner at the top of your dashboard." class="lg:col-span-2">
            <livewire:admin.dashboard-image-picker :model="$user" :key="'picker-user-'.$user->id" />
        </x-admin.card>
    </div>
</div>
