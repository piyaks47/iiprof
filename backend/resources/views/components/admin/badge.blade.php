@props(['color' => 'slate'])
@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'red' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'gold' => 'bg-gold-50 text-gold-700 ring-gold-500/30',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.($colors[$color] ?? $colors['slate'])]) }}>{{ $slot }}</span>
