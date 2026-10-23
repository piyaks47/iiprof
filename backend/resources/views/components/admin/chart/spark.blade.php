@props(['points' => [], 'stroke' => 'stroke-gold-500', 'fill' => 'fill-gold-400/15'])
{{-- Tiny trend line for KPI tiles. --}}
@php
    $n = count($points); $max = max(1, ...($points ?: [0])); $w = 100; $h = 28;
    $xy = collect($points)->map(fn ($v, $i) => [($n > 1 ? $i / ($n - 1) : 0) * $w, $h - 2 - $v / $max * ($h - 4)]);
    $line = $xy->map(fn ($p) => round($p[0], 2).','.round($p[1], 2))->implode(' ');
@endphp
@if ($n > 1)
    <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" {{ $attributes->merge(['class' => 'h-7 w-full']) }} aria-hidden="true">
        <polygon points="0,{{ $h }} {{ $line }} {{ $w }},{{ $h }}" class="{{ $fill }}" />
        <polyline points="{{ $line }}" fill="none" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" class="{{ $stroke }}" />
    </svg>
@endif
