{{-- Enquiry analytics: per day, by kind / status, top registrations. Shown to people who may see enquiries. --}}
@if ($perDay !== null)
    <x-admin.card title="Enquiries & registrations" description="Last 30 days, per day." class="lg:col-span-2">
        <x-slot:actions>
            <a href="{{ route('admin.resources.index', 'enquiries') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:text-brand-500">Inbox &rarr;</a>
        </x-slot:actions>
        <x-admin.chart.columns :data="$perDay" aria-label="Enquiries per day"
            :series="['registrations' => ['Registrations & membership', 'fill-gold-500'], 'other' => ['Other enquiries', 'fill-brand-700']]" />
        @php
            $total = fn ($d) => $d['registrations'] + $d['other'];
            $busiest = collect($perDay)->sortByDesc($total)->first();
            $summary = [
                ['Registrations & membership', array_sum(array_column($perDay, 'registrations'))],
                ['Other enquiries', array_sum(array_column($perDay, 'other'))],
                ['Last 7 days', collect(array_slice($perDay, -7))->sum($total)],
                ['Busiest day', $busiest && $total($busiest) ? $busiest['label'].' · '.$total($busiest) : '—'],
            ];
        @endphp
        <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-100 pt-4 sm:grid-cols-4">
            @foreach ($summary as [$label, $value])
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                    <dd class="mt-0.5 font-display text-xl font-semibold tabular-nums text-brand-950">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </x-admin.card>

    <x-admin.card title="Enquiries by status">
        <x-admin.chart.donut label="Enquiries" :segments="[
            ['New', $byStatus['New'] ?? 0, 'stroke-gold-500'],
            ['In progress', $byStatus['In progress'] ?? 0, 'stroke-sky-500'],
            ['Closed', $byStatus['Closed'] ?? 0, 'stroke-brand-700'],
        ]" />
        <div class="mt-5 border-t border-slate-100 pt-4">
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">By kind (all time)</p>
            <x-admin.chart.bars :items="collect($byKind)->mapWithKeys(fn ($n, $k) => [\App\Support\Dashboard\Metrics::KINDS[$k] => $n])->all()" fill="bg-gold-500" />
        </div>
    </x-admin.card>

    <x-admin.card title="Most requested" description="Programmes, events and categories people registered for.">
        <x-admin.chart.bars :items="$topRegistrations->all()" fill="bg-brand-600" empty="No registrations yet — they appear when visitors use the Register buttons." />
    </x-admin.card>
@endif
