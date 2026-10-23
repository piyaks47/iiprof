@props(['label' => null, 'type' => 'text', 'hint' => null, 'prefix' => null, 'id' => null])
@php
    $model = $attributes->wire('model')->value();
    $id = $id ?? 'f_'.str_replace(['.', '-'], '_', $model ?: uniqid());
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <div @class(['mt-1.5' => $label, 'flex rounded-xl shadow-sm' => $prefix])>
        @if ($prefix)
            <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">{{ $prefix }}</span>
        @endif
        <input id="{{ $id }}" type="{{ $type }}"
            {{ $attributes->except('class')->class([
                'block w-full border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-gold-400 focus:ring-gold-400/40',
                'rounded-xl shadow-sm' => ! $prefix,
                'rounded-r-xl' => $prefix,
                'border-rose-400 focus:border-rose-500 focus:ring-rose-500' => $model && $errors->has($model),
            ]) }}>
    </div>
    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($model)
        @error($model)<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @endif
</div>
