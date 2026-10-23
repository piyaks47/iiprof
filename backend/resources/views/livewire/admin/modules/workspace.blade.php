<div>
    <section class="relative isolate mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-fuchsia-600 px-6 py-8 shadow-sm sm:px-8">
        <div aria-hidden="true" class="absolute -right-16 -top-16 -z-10 size-64 rounded-full bg-white/10 blur-2xl"></div>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                <x-admin.icon :name="$module->icon" class="size-8" />
            </span>
            <div class="flex-1">
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ $module->name }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-white/80">{{ $module->description }}</p>
            </div>
            @can('manage-modules')
                <a href="{{ route('admin.modules.edit', $module->id) }}" wire:navigate class="inline-flex items-center gap-2 self-start rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/25 hover:bg-white/25">
                    <x-admin.icon name="lock" class="size-4" /> Manage access
                </a>
            @endcan
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Who is in {{ $module->name }}" description="User types that have this module, with their subtypes." :padding="false">
                <ul class="divide-y divide-slate-100">
                    @forelse ($types as $type)
                        <li class="flex gap-4 px-5 py-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-admin.icon :name="$type->icon" class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900">{{ $type->name }}
                                    @if ($user->user_type_id === $type->id)<x-admin.badge color="brand" class="ml-1">You</x-admin.badge>@endif
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @forelse ($type->subtypes as $subtype)
                                        <x-admin.badge :color="$user->user_subtype_id === $subtype->id ? 'brand' : 'slate'">{{ $subtype->name }}</x-admin.badge>
                                    @empty
                                        <span class="text-xs text-slate-400">No subtypes</span>
                                    @endforelse
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-500">This module is given by role, not by user type.</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card>
                <x-admin.empty-state icon="puzzle" title="{{ $module->name }} tools will appear here"
                    description="This workspace is ready and access-controlled. Listings, requests and bookings for this module are the next features to build." />
            </x-admin.card>
        </div>

        <x-admin.card title="Your access">
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-2"><x-admin.icon :name="$user->isAdmin() || $viaRole ? 'check' : 'close'" @class(['size-5', 'text-emerald-500' => $user->isAdmin() || $viaRole, 'text-slate-300' => ! ($user->isAdmin() || $viaRole)]) />
                    {{ $user->isAdmin() ? 'Administrator (all modules)' : 'Through your role ('.$user->role->label().')' }}</li>
                <li class="flex items-center gap-2"><x-admin.icon :name="$viaType ? 'check' : 'close'" @class(['size-5', 'text-emerald-500' => $viaType, 'text-slate-300' => ! $viaType]) />
                    Through your user type{{ $user->userType ? ' ('.$user->userType->name.')' : '' }}</li>
            </ul>
        </x-admin.card>
    </div>
</div>
