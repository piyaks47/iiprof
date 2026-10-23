<div>
    <x-admin.dashboard-header :user="$user" :subtitle="$user->userType?->description ?? 'Your personal dashboard.'">
        <x-admin.button size="sm" variant="secondary" icon="user" :href="route('admin.profile')" wire:navigate>My profile</x-admin.button>
    </x-admin.dashboard-header>

    @unless ($user->userType)
        <div class="mb-6 flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-200">
            <x-admin.icon name="alert" class="size-5 shrink-0" />
            <p>Your account doesn’t have a <span class="font-semibold">user type</span> yet, so only general modules are shown. An administrator will assign one (for example Educators, Job Seekers or Lenders).</p>
        </div>
    @endunless

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Your modules</h2>
            <x-admin.module-grid :modules="$modules" empty="An administrator assigns modules through your user type." />
        </div>

        <div class="space-y-6">
            <x-admin.card title="My account">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Name</dt><dd class="text-right font-medium text-slate-900">{{ $user->name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd class="truncate text-right font-medium text-slate-900">{{ $user->email }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">User type</dt><dd class="text-right font-medium text-slate-900">{{ $user->userType?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Subtype</dt><dd class="text-right font-medium text-slate-900">{{ $user->userSubtype?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Member since</dt><dd class="text-right font-medium text-slate-900">{{ $user->created_at->format('M Y') }}</dd></div>
                </dl>
                <x-admin.button variant="secondary" size="sm" icon="user" class="mt-5 w-full" :href="route('admin.profile')" wire:navigate>Edit profile</x-admin.button>
            </x-admin.card>
        </div>
    </div>
</div>
