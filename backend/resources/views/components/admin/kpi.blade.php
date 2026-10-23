@props(['label', 'value', 'icon' => 'chart', 'href' => null, 'trend' => null, 'spark' => null, 'hint' => null])
{{-- Compact KPI tile: value, label, optional trend (% vs previous period) and sparkline. --}}
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'group flex flex-col rounded-xl bg-white p-4 shadow-[0_1px_2px_rgb(7_15_32/0.04)] ring-1 ring-slate-200/80 transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_32px_-18px_rgb(7_15_32/0.3)] hover:ring-gold-300/60']) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-800 to-brand-950 text-gold-300"><x-admin.icon :name="$icon" class="size-4" /></span>
        @if ($trend !== null)
            @php $up = $trend['delta'] >= 0; @endphp
            <span @class(['inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums', 'bg-emerald-50 text-emerald-700' => $up && $trend['delta'] > 0, 'bg-rose-50 text-rose-700' => ! $up, 'bg-slate-100 text-slate-500' => $trend['delta'] === 0]) title="Last 30 days vs the 30 days before">
                <x-admin.icon :name="$up ? 'arrow-up' : 'arrow-down'" class="size-3" />{{ abs($trend['delta']) }}%
            </span>
        @endif
    </div>
    <p class="mt-3 font-display text-2xl font-semibold tabular-nums tracking-tight text-brand-950"><span x-data="countUp({{ (int) $value }})" x-text="display">{{ number_format($value) }}</span></p>
    <p class="text-xs font-medium text-slate-500 group-hover:text-brand-600">{{ $label }}</p>
    @if ($hint)<p class="mt-0.5 text-[11px] text-slate-400">{{ $hint }}</p>@endif
    @if ($spark)<x-admin.chart.spark :points="$spark" class="mt-2 h-6 w-full" />@endif
</{{ $tag }}>
