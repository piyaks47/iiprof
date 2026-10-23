@props(['title', 'description' => null, 'back' => null])
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" wire:navigate class="group mb-3 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400 transition hover:text-brand-700">
                <span class="transition group-hover:-translate-x-0.5">&larr;</span> Back
            </a>
        @endif
        <h1 class="truncate font-display text-[1.85rem] font-semibold leading-tight tracking-tight text-brand-950">{{ $title }}</h1>
        <span class="mt-2 block h-0.5 w-12 rounded-full bg-gradient-to-r from-gold-400 to-gold-200"></span>
        @if ($description)
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
