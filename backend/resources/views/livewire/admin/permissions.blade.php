<div>
    <x-admin.page-header title="Role permissions"
        description="Choose what editors and users can open in the admin panel. The sidebar shows each person only the areas they can view.">
        <x-slot:actions>
            <x-admin.button variant="secondary" wire:click="restoreDefaults">Restore defaults</x-admin.button>
            <x-admin.button icon="check" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save permissions</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        @foreach ([['No access', 'Hidden from the sidebar; the URL returns 403.', 'slate'], ['View', 'Can open lists and records read-only.', 'sky'], ['Manage', 'Can create, edit, reorder, switch off and delete.', 'green']] as [$name, $text, $color])
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <x-admin.badge :color="$color">{{ $name }}</x-admin.badge>
                <p class="mt-2 text-xs text-slate-500">{{ $text }}</p>
            </div>
        @endforeach
    </div>

    <x-admin.card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Area</th>
                        <th class="px-5 py-3 text-center">Administrator</th>
                        @foreach ($roles as $role)
                            <th class="px-5 py-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $role->label() }}</span>
                                    <span class="flex gap-1 normal-case tracking-normal">
                                        @foreach ($levels as $value => $levelLabel)
                                            <button type="button" wire:click="setAll('{{ $role->value }}', '{{ $value }}')" class="rounded px-1.5 py-0.5 text-[11px] font-medium text-slate-500 hover:bg-slate-200 hover:text-slate-800" title="Set every area to {{ $levelLabel }}">{{ $value === 0 ? 'None' : $levelLabel }}</button>
                                        @endforeach
                                    </span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                @foreach ($groups as $group => $areas)
                    <tbody class="divide-y divide-slate-100 border-t border-slate-200">
                        <tr><th colspan="{{ 2 + count($roles) }}" class="bg-slate-50 px-5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</th></tr>
                        @foreach ($areas as $area => $definition)
                            <tr wire:key="area-{{ $area }}">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $definition['label'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $definition['description'] }}</p>
                                </td>
                                <td class="px-5 py-3 text-center"><x-admin.badge color="green">Full</x-admin.badge></td>
                                @foreach ($roles as $role)
                                    <td class="px-5 py-3">
                                        <div class="inline-flex rounded-lg bg-slate-100 p-0.5" role="radiogroup" aria-label="{{ $role->label() }} — {{ $definition['label'] }}">
                                            @foreach ($levels as $value => $levelLabel)
                                                <label @class([
                                                    'cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition',
                                                    'bg-white text-slate-900 shadow-sm' => ($matrix[$role->value][$area] ?? '0') === (string) $value,
                                                    'text-slate-500 hover:text-slate-800' => ($matrix[$role->value][$area] ?? '0') !== (string) $value,
                                                ])>
                                                    <input type="radio" class="sr-only" wire:model.live="matrix.{{ $role->value }}.{{ $area }}" value="{{ $value }}">
                                                    {{ $value === 0 ? 'None' : $levelLabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
                <tbody class="border-t border-slate-200">
                    <tr><th colspan="{{ 2 + count($roles) }}" class="bg-slate-50 px-5 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Administration (fixed)</th></tr>
                    <tr>
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-900">Users, User types, Module access, Role permissions</p>
                            <p class="text-xs text-slate-500">Always administrators only.</p>
                        </td>
                        <td class="px-5 py-3 text-center"><x-admin.badge color="green">Full</x-admin.badge></td>
                        @foreach ($roles as $role)
                            <td class="px-5 py-3"><x-admin.badge color="slate">None</x-admin.badge></td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="mt-6 flex flex-col gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-900 ring-1 ring-brand-100 sm:flex-row sm:items-center sm:justify-between">
        <p><strong>Modules</strong> (Community, Hunar, Jeevika, Khazana …) are given by role and user type, not here.</p>
        <x-admin.button variant="secondary" size="sm" icon="lock" :href="route('admin.modules.index')" wire:navigate>Module access</x-admin.button>
    </div>
</div>
