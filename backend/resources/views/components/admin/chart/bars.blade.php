@props(['items' => [], 'fill' => 'bg-brand-600', 'empty' => 'Nothing yet.'])
{{-- Horizontal bars: $items = [label => value] (or list of [label, value, href?]). --}}
@php
    $rows = collect($items)->map(fn ($v, $k) => is_array($v) ? $v : [$k, $v, null])->values();
    $max = max(1, (int) $rows->max(fn ($r) => $r[1]));
@endphp
@if ($rows->isEmpty() || $rows->sum(fn ($r) => $r[1]) === 0)
    <p class="py-4 text-sm text-slate-400">{{ $empty }}</p>
@else
    <ul {{ $attributes->merge(['class' => 'space-y-2.5']) }}>
        @foreach ($rows as [$label, $value, $href])
            <li>
                <div class="flex items-baseline justify-between gap-3 text-xs">
                    @if ($href)<a href="{{ $href }}" wire:navigate class="truncate font-medium text-slate-700 hover:text-brand-600">{{ $label }}</a>@else<span class="truncate font-medium text-slate-700">{{ $label }}</span>@endif
                    <span class="shrink-0 font-semibold tabular-nums text-slate-900">{{ $value }}</span>
                </div>
                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full origin-left rounded-full {{ $fill }} motion-safe:animate-[grow-x_0.8s_var(--ease-out-expo)_both]" style="width: {{ max(2, round($value / $max * 100)) }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
