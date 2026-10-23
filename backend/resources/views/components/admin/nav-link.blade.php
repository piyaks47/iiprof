@props(['href', 'icon', 'active' => false, 'badge' => null])
<a href="{{ $href }}" wire:navigate data-tip="{{ trim(strip_tags($slot)) }}" x-on:mouseenter="hint($el)" x-on:mouseleave="tip.show = false"
    @class([
        'sb-link group flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium',
        'bg-gradient-to-r from-white/[0.12] to-white/[0.04] text-white shadow-[inset_0_0_0_1px_rgb(255_255_255/0.06)]' => $active,
        'text-slate-300/90 hover:bg-white/[0.05] hover:text-white' => ! $active,
    ])
    @if ($active) aria-current="page" @endif>
    <x-admin.icon :name="$icon" @class(['size-5 shrink-0 transition', 'text-gold-300' => $active, 'text-slate-500 group-hover:text-slate-200' => ! $active]) />
    <span class="sb-hide-rail sb-label min-w-0 flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="sb-badge rounded-full bg-gold-400 px-1.5 py-0.5 text-[10px] font-bold leading-none text-brand-950 shadow-[0_0_10px_rgb(209_170_90/0.6)]">{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
