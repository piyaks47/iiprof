@props(['data' => [], 'series' => [], 'height' => 176])
{{--
    Stacked columns (one per day). $data: list of ['label' => '1 Oct', '<series key>' => n, …];
    $series: [key => [label, fill class]]. Plain SVG + CSS, no chart library.
--}}
@php
    $max = max(1, ...array_map(fn ($d) => array_sum(array_map(fn ($k) => (int) ($d[$k] ?? 0), array_keys($series))), $data ?: [[]]));
    $n = max(1, count($data));
    $w = 10; $gap = 3; $width = $n * ($w + $gap);
    $ticks = [0, (int) ceil($max / 2), $max];
@endphp
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="flex gap-3">
        <div class="flex flex-col justify-between py-0.5 text-right text-[10px] tabular-nums text-slate-400" style="height: {{ $height }}px">
            @foreach (array_reverse($ticks) as $t)<span>{{ $t }}</span>@endforeach
        </div>
        <div class="relative min-w-0 flex-1">
            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                @foreach ($ticks as $t)<span class="border-t border-dashed border-slate-200"></span>@endforeach
            </div>
            <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="relative block w-full" style="height: {{ $height }}px" role="img" aria-label="{{ $attributes->get('aria-label', 'Chart') }}">
                @foreach ($data as $i => $d)
                    @php $y = $height; @endphp
                    <g class="transition-opacity hover:opacity-80">
                        <title>{{ $d['label'] }}: {{ collect($series)->map(fn ($s, $k) => $s[0].' '.($d[$k] ?? 0))->implode(', ') }}</title>
                        <rect x="{{ $i * ($w + $gap) }}" y="0" width="{{ $w }}" height="{{ $height }}" fill="transparent" />
                        @foreach ($series as $key => [$label, $fill])
                            @php $h = ($d[$key] ?? 0) / $max * ($height - 2); $y -= $h; @endphp
                            @if ($h > 0)
                                <rect x="{{ $i * ($w + $gap) }}" y="{{ $y }}" width="{{ $w }}" height="{{ $h }}" rx="1.5" class="chart-col {{ $fill }} motion-safe:animate-[grow_0.7s_var(--ease-out-expo)_both]" style="animation-delay: {{ $i * 12 }}ms" />
                            @endif
                        @endforeach
                    </g>
                @endforeach
            </svg>
            <div class="mt-1.5 flex justify-between text-[10px] text-slate-400">
                <span>{{ $data[0]['label'] ?? '' }}</span><span>{{ $data[intdiv($n, 2)]['label'] ?? '' }}</span><span>{{ $data[$n - 1]['label'] ?? '' }}</span>
            </div>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
        @foreach ($series as [$label, $fill])
            <span class="inline-flex items-center gap-1.5"><svg class="size-2.5" viewBox="0 0 10 10"><rect width="10" height="10" rx="2" class="{{ $fill }}" /></svg>{{ $label }}</span>
        @endforeach
    </div>
</div>
