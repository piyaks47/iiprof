@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'type' => 'button'])
@php
    $base = 'relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl font-semibold transition duration-200 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-3 text-sm',
    ];
    $variants = [
        'primary' => 'bg-brand-900 text-white shadow-[0_6px_16px_-6px_rgb(7_15_32/0.55)] hover:bg-brand-800 hover:shadow-[0_10px_22px_-8px_rgb(7_15_32/0.6)] focus-visible:ring-gold-400 [&_svg]:text-gold-300',
        'gold' => 'bg-gradient-to-r from-gold-400 to-gold-500 text-brand-950 shadow-[0_8px_20px_-8px_rgb(192_148_67/0.8)] hover:from-gold-300 hover:to-gold-500 focus-visible:ring-gold-400',
        'secondary' => 'bg-white text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 focus-visible:ring-brand-500',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-500 focus-visible:ring-rose-500',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:ring-brand-500',
        'ghost-danger' => 'text-rose-600 hover:bg-rose-50 focus-visible:ring-rose-500',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
    </button>
@endif
