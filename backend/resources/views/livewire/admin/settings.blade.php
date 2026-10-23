<div>
    <x-admin.page-header title="Site settings" description="Shown on the website and returned by the API (/api/v1/site).">
        @unless ($canManage)
            <x-slot:actions><x-admin.badge color="slate"><x-admin.icon name="eye" class="size-3.5" /> View only</x-admin.badge></x-slot:actions>
        @endunless
    </x-admin.page-header>

    <form wire:submit="save" novalidate>
    <fieldset @disabled(! $canManage) class="grid min-w-0 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @foreach ($groups as $group => $fields)
                <x-admin.card :title="$group">
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($fields as $key => $field)
                            @if ($field['type'] === 'textarea')
                                <x-admin.textarea :label="$field['label']" wire:model="values.{{ $key }}" rows="3" class="sm:col-span-2" />
                            @else
                                <x-admin.input :label="$field['label']" :type="$field['type'] === 'url' ? 'url' : ($field['type'] === 'email' ? 'email' : 'text')"
                                    wire:model="values.{{ $key }}" :hint="$field['hint'] ?? null"
                                    :placeholder="$field['type'] === 'url' ? 'https://' : ($field['type'] === 'link' ? '/page or https://…' : null)" />
                            @endif
                        @endforeach
                    </div>
                </x-admin.card>
            @endforeach
        </div>

        <div class="space-y-6">
            <x-admin.card title="Logo" description="PNG, JPG, WebP or SVG, up to 2 MB.">
                <div class="space-y-4">
                    <div class="flex h-28 items-center justify-center rounded-lg bg-slate-50 ring-1 ring-slate-200">
                        @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                            <img src="{{ $logo->temporaryUrl() }}" alt="" class="max-h-20 max-w-[80%] object-contain">
                        @elseif ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Current logo" class="max-h-20 max-w-[80%] object-contain">
                        @else
                            <span class="text-xs text-slate-400">No logo uploaded</span>
                        @endif
                    </div>
                    <input type="file" wire:model="logo" accept="image/*"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <div wire:loading wire:target="logo" class="text-xs text-slate-500">Uploading…</div>
                    @error('logo')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    @if ($logoUrl && ! $logo && $canManage)
                        <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="removeLogo" wire:confirm="Remove the logo?">Remove logo</x-admin.button>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Default page banner" description="Used on every page that has no banner image of its own. Wide photo, at least 1920 × 600 px, up to 6 MB.">
                <div class="space-y-4">
                    <div class="relative flex aspect-[16/5] items-center justify-center overflow-hidden rounded-xl bg-brand-950 ring-1 ring-slate-200">
                        @if ($banner && method_exists($banner, 'isPreviewable') && $banner->isPreviewable())
                            <img src="{{ $banner->temporaryUrl() }}" alt="" class="absolute inset-0 size-full object-cover">
                        @elseif ($bannerUrl)
                            <img src="{{ $bannerUrl }}" alt="Current default banner" class="absolute inset-0 size-full object-cover" onerror="this.remove()">
                        @endif
                        <span class="relative text-xs font-medium text-white/60">{{ $bannerUrl || $banner ? '' : 'Navy pattern (no image)' }}</span>
                    </div>
                    <input type="file" wire:model="banner" accept="image/*" aria-label="Default page banner"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <div wire:loading wire:target="banner" class="text-xs text-slate-500">Uploading…</div>
                    @error('banner')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    @if ($bannerUrl && ! $banner && $canManage)
                        <x-admin.button variant="ghost-danger" size="sm" icon="trash" wire:click="removeBanner" wire:confirm="Remove the default banner?">Remove banner</x-admin.button>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Sign-in page" description="The page where administrators, editors and members sign in.">
                <div class="space-y-6">
                    @foreach ($extraImages as $key => $image)
                        @php($upload = $images[$key] ?? null)
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ $image['label'] }}</p>
                            <div @class(['relative mt-1.5 flex items-center justify-center overflow-hidden rounded-xl ring-1 ring-slate-200', 'h-28 bg-slate-50' => $key === 'login_logo', 'aspect-[16/7] bg-brand-950' => $key !== 'login_logo'])>
                                @if ($upload && method_exists($upload, 'isPreviewable') && $upload->isPreviewable())
                                    <img src="{{ $upload->temporaryUrl() }}" alt="" @class(['max-h-20 max-w-[80%] object-contain' => $key === 'login_logo', 'absolute inset-0 size-full object-cover' => $key !== 'login_logo'])>
                                @elseif ($image['url'])
                                    <img src="{{ $image['url'] }}" alt="Current {{ strtolower($image['label']) }}" onerror="this.remove()" @class(['max-h-20 max-w-[80%] object-contain' => $key === 'login_logo', 'absolute inset-0 size-full object-cover' => $key !== 'login_logo'])>
                                @else
                                    <span @class(['text-xs', 'text-slate-400' => $key === 'login_logo', 'text-white/60' => $key !== 'login_logo'])>{{ $key === 'login_logo' ? 'Uses the logo' : 'Navy pattern (no image)' }}</span>
                                @endif
                            </div>
                            <input type="file" wire:model="images.{{ $key }}" accept="image/*" aria-label="{{ $image['label'] }}"
                                class="mt-2 block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                            <div wire:loading wire:target="images.{{ $key }}" class="mt-1 text-xs text-slate-500">Uploading…</div>
                            <p class="mt-1.5 text-xs text-slate-500">{{ $image['hint'] }}</p>
                            @error('images.'.$key)<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            @if ($image['url'] && ! $upload && $canManage)
                                <x-admin.button variant="ghost-danger" size="sm" icon="trash" class="mt-2" wire:click="removeImage('{{ $key }}')" wire:confirm="Remove the {{ strtolower($image['label']) }}?">Remove</x-admin.button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-admin.card>

            @if ($canManage)
                <div class="flex justify-end">
                    <x-admin.button type="submit" wire:loading.attr="disabled" wire:target="save,logo,banner,images">Save settings</x-admin.button>
                </div>
            @endif
        </div>
    </fieldset>
    </form>
</div>
