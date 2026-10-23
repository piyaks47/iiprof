@props(['title' => null, 'description' => null, 'padding' => true])
<section {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-[0_1px_2px_rgb(7_15_32/0.04),0_8px_24px_-12px_rgb(7_15_32/0.08)] ring-1 ring-slate-200/80']) }}>
    @if ($title || isset($actions))
        <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                @if ($title)<h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
