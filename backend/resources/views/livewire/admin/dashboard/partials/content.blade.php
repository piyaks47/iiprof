{{-- What the website holds, with links to the screens the user may open. --}}
<x-admin.card title="Website content" :padding="false">
    <ul class="grid grid-cols-2 divide-x divide-y divide-slate-100 [&>li:nth-child(-n+2)]:border-t-0">
        @foreach ($content as $item)
            @php
                $route = $item['route'] === 'pages' ? (Gate::allows('view-pages') ? route('admin.pages.index') : null)
                    : (($res = \App\Admin\Registry::find($item['route'])) && Gate::allows('view-'.$res->area()) ? $res->url() : null);
            @endphp
            <li class="first:border-l-0 [&:nth-child(odd)]:border-l-0">
                <{{ $route ? 'a' : 'div' }} @if ($route) href="{{ $route }}" wire:navigate @endif class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-gold-50 ring-1 ring-gold-200/60"><x-admin.icon :name="$item['icon']" class="size-4 text-gold-600" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-brand-950">{{ $item['value'] }}</span>
                        <span class="block text-[11px] leading-tight text-slate-500">{{ $item['label'] }}</span>
                    </span>
                </{{ $route ? 'a' : 'div' }}>
            </li>
        @endforeach
    </ul>
</x-admin.card>
