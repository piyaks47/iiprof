@props(['user', 'subtitle' => null, 'live' => false])
{{-- Compact top of every dashboard: the dashboard image as a thumbnail, role / type, actions. --}}
<header class="mb-5 flex flex-wrap items-center gap-4">
    <a href="{{ route('admin.profile') }}#dashboard-image" wire:navigate title="Change the dashboard image"
        class="group relative size-12 shrink-0 overflow-hidden rounded-xl bg-brand-950 shadow-sm ring-1 ring-brand-950/10">
        <img src="{{ $user->dashboardImageUrl() }}" alt="" class="size-full object-cover transition duration-500 group-hover:scale-110">
        <span class="absolute inset-0 flex items-center justify-center bg-brand-950/60 text-white opacity-0 transition group-hover:opacity-100"><x-admin.icon name="photo" class="size-4" /></span>
    </a>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="font-display text-2xl font-semibold tracking-tight text-brand-950">Dashboard</h1>
            <x-admin.badge color="brand">{{ $user->role->label() }}</x-admin.badge>
            @if ($user->typeLabel())<x-admin.badge color="slate">{{ $user->typeLabel() }}</x-admin.badge>@endif
        </div>
        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $subtitle ?? $user->role->description() }}</p>
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-2">
        @if ($live)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200" title="Refreshes every 30 seconds">
                <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-1.5 rounded-full bg-emerald-500"></span></span>
                Live · {{ now()->format('H:i:s') }}
            </span>
        @endif
        {{ $slot }}
    </div>
</header>
