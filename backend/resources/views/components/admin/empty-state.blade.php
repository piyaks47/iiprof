@props(['icon' => 'document', 'title', 'description' => null])
<div class="px-6 py-14 text-center">
    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100">
        <x-admin.icon :name="$icon" class="size-6 text-slate-400" />
    </div>
    <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-5">{{ $slot }}</div>@endif
</div>
