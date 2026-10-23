@props(['modules', 'empty' => 'No modules have been assigned to you yet.'])
@if ($modules->isEmpty())
    <x-admin.card>
        <x-admin.empty-state icon="puzzle" title="No modules yet" :description="$empty" />
    </x-admin.card>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($modules as $module)
            <a href="{{ $module->url() }}" wire:navigate wire:key="module-{{ $module->id }}"
                class="group flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-brand-200">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-600 ring-1 ring-brand-100">
                        <x-admin.icon :name="$module->icon" class="size-6" />
                    </span>
                    <x-admin.icon name="arrow-right" class="size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500" />
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-900 group-hover:text-brand-700">{{ $module->name }}</h3>
                <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $module->description }}</p>
            </a>
        @endforeach
    </div>
@endif
