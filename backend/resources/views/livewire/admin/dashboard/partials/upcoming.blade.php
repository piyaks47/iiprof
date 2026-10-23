<x-admin.card title="Coming up" description="Next events and training batches." :padding="false">
    <ul class="divide-y divide-slate-100">
        @forelse ($upcoming as $row)
            @php $res = \App\Admin\Registry::find($row['resource']); $can = $res && Gate::allows('view-'.$res->area()); @endphp
            <li class="flex items-center gap-3 px-5 py-2.5">
                <span class="flex w-11 shrink-0 flex-col items-center rounded-lg bg-slate-50 py-1 ring-1 ring-slate-200">
                    <span class="font-display text-base font-semibold leading-none text-brand-950">{{ $row['date']->format('d') }}</span>
                    <span class="mt-0.5 text-[9px] font-bold uppercase tracking-wider text-gold-700">{{ $row['date']->format('M') }}</span>
                </span>
                <div class="min-w-0 flex-1">
                    @if ($can)
                        <a href="{{ route('admin.resources.edit', [$row['resource'], $row['id']]) }}" wire:navigate class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $row['title'] }}</a>
                    @else
                        <p class="truncate text-sm font-medium text-slate-900">{{ $row['title'] }}</p>
                    @endif
                    <p class="truncate text-xs text-slate-500">{{ $row['meta'] ?: '—' }}</p>
                </div>
                <x-admin.badge :color="$row['kind'] === 'Event' ? 'gold' : 'sky'">{{ $row['kind'] }}</x-admin.badge>
            </li>
        @empty
            <li><x-admin.empty-state title="Nothing scheduled" description="Add events or training batches." /></li>
        @endforelse
    </ul>
</x-admin.card>
