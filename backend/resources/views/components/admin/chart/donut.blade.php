@props(['segments' => [], 'size' => 132, 'label' => 'Total'])
{{-- $segments: list of [label, value, stroke class]. --}}
@php
    $total = array_sum(array_column($segments, 1));
    $r = 15.9155; $offset = 25; // circumference 100, start at 12 o'clock
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-5']) }}>
    <svg viewBox="0 0 42 42" class="shrink-0 -rotate-0" style="width: {{ $size }}px; height: {{ $size }}px" role="img" aria-label="{{ $label }}: {{ $total }}">
        <circle cx="21" cy="21" r="{{ $r }}" fill="none" class="stroke-slate-100" stroke-width="5" />
        @foreach ($segments as [$segLabel, $value, $stroke])
            @if ($total > 0 && $value > 0)
                @php $pct = $value / $total * 100; @endphp
                <circle cx="21" cy="21" r="{{ $r }}" fill="none" class="{{ $stroke }} transition-all duration-700" stroke-width="5"
                    stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-dashoffset="{{ $offset }}"><title>{{ $segLabel }}: {{ $value }}</title></circle>
                @php $offset -= $pct; @endphp
            @endif
        @endforeach
        <text x="21" y="20.5" text-anchor="middle" class="fill-slate-900 font-display text-[8px] font-semibold">{{ $total }}</text>
        <text x="21" y="26.5" text-anchor="middle" class="fill-slate-400 text-[3.2px] uppercase tracking-wider">{{ $label }}</text>
    </svg>
    <ul class="min-w-0 flex-1 space-y-2 text-xs">
        @foreach ($segments as [$segLabel, $value, $stroke])
            <li class="flex items-center gap-2">
                <svg class="size-2.5 shrink-0" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="none" stroke-width="3" class="{{ $stroke }}" /></svg>
                <span class="truncate text-slate-600">{{ $segLabel }}</span>
                <span class="ml-auto font-semibold tabular-nums text-slate-900">{{ $value }}</span>
            </li>
        @endforeach
    </ul>
</div>
