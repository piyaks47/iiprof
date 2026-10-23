@props(['label', 'description' => null, 'disabled' => false])
@php $model = $attributes->wire('model')->value(); @endphp
<label @class(['flex items-start justify-between gap-4', 'cursor-pointer' => ! $disabled, 'opacity-60' => $disabled])>
    <span>
        <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
        @if ($description)
            <span class="block text-xs text-slate-500">{{ $description }}</span>
        @endif
    </span>
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" class="peer sr-only" @disabled($disabled) {{ $attributes }}>
        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-gold-400 peer-focus-visible:ring-offset-2"></span>
        <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
@if ($model)
    @error($model)<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
@endif
