<div class="space-y-4">
    <div class="relative overflow-hidden rounded-xl ring-1 ring-slate-200">
        <img src="{{ $currentUrl }}" alt="Current dashboard image" class="aspect-[10/3] max-h-56 w-full object-cover">
        <span class="absolute left-3 top-3 rounded-full bg-slate-900/70 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur">
            {{ $value ? ($isUpload ? 'Uploaded image' : 'Preset') : 'Using '.$fallback }}
        </span>
        <div wire:loading.flex wire:target="upload,choose,useDefault" class="absolute inset-0 items-center justify-center bg-white/60 text-xs font-semibold text-slate-700">Saving…</div>
    </div>

    <div>
        <p class="mb-2 text-xs font-medium text-slate-500">Choose a preset</p>
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
            @foreach ($presets as $preset)
                <button type="button" wire:click="choose('{{ $preset['value'] }}')" title="{{ $preset['label'] }}"
                    @class([
                        'group relative overflow-hidden rounded-lg ring-2 transition',
                        'ring-brand-500' => $value === $preset['value'],
                        'ring-transparent hover:ring-slate-300' => $value !== $preset['value'],
                    ])>
                    <img src="{{ $preset['url'] }}" alt="" class="aspect-[3/2] w-full object-cover">
                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-1.5 pb-1 pt-3 text-left text-[10px] font-semibold text-white">{{ $preset['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <label class="cursor-pointer">
            <span class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                <x-admin.icon name="photo" class="size-4" /> Upload your own
            </span>
            <input type="file" wire:model="upload" accept="image/png,image/jpeg,image/webp" class="sr-only">
        </label>
        @if ($value)
            <x-admin.button variant="ghost" size="sm" wire:click="useDefault">Use default</x-admin.button>
        @endif
        <span class="text-xs text-slate-400">JPG, PNG or WebP · wide images (1600×480) look best · max 4 MB</span>
    </div>
    @error('upload')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
</div>
