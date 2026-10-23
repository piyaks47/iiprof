@props(['label' => null, 'options' => [], 'placeholder' => null, 'hint' => null, 'id' => null])
@php
    $model = $attributes->wire('model')->value();
    $id = $id ?? 'f_'.str_replace(['.', '-'], '_', $model ?: uniqid());
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <select id="{{ $id }}"
        {{ $attributes->except('class')->class([
            'block w-full rounded-xl border-slate-300 py-2 pl-3 pr-9 text-sm text-slate-900 shadow-sm focus:border-gold-400 focus:ring-gold-400/40',
            'mt-1.5' => $label,
        ]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($model)
        @error($model)<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
    @endif
</div>
