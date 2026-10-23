<div>
    <x-admin.page-header :title="$title" :back="route('admin.pages.index')">
        <x-slot:actions>
            @unless ($canManage)
                <x-admin.badge color="slate"><x-admin.icon name="eye" class="size-3.5" /> View only</x-admin.badge>
            @endunless
            @if ($publicUrl)
                <x-admin.button variant="secondary" icon="external" :href="$publicUrl" target="_blank">View on site</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" novalidate>
    <fieldset @disabled(! $canManage) class="grid min-w-0 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card>
                <div class="space-y-5">
                    <x-admin.input label="Title" wire:model.live.debounce.400ms="form.title" required placeholder="e.g. Our services" class="[&_input]:text-base [&_input]:font-medium" />
                    <x-admin.input label="Slug (URL)" wire:model.live.blur="form.slug" :prefix="rtrim(preg_replace('#^https?://#', '', config('app.url')), '/').'/'"
                        :hint="$form->slug === 'home' ? 'The page with slug “home” is shown at the site root.' : 'Leave empty to generate from the title.'" />
                    <x-admin.input label="Website address (optional)" wire:model.live.blur="form.public_path" placeholder="{{ '/'.($form->slug ?: 'page-slug') }}"
                        hint="Answer at a nested address such as /industry/banks-nbfc instead of /{{ $form->slug ?: 'slug' }}." />
                </div>
            </x-admin.card>

            <x-admin.card title="Banner" description="The image band at the top of the page. Empty fields fall back to the page title, the excerpt and the default banner in Settings.">
                @php
                    $current = $form->page?->featured_image_url;
                    $previewImage = ($image && method_exists($image, 'isPreviewable') && $image->isPreviewable()) ? $image->temporaryUrl() : (($current && ! $removeImage) ? $current : $defaultBanner);
                @endphp
                {{-- Live preview --}}
                <div class="relative mb-6 overflow-hidden rounded-2xl bg-brand-950 ring-1 ring-brand-950/10">
                    @if ($previewImage)
                        <img src="{{ $previewImage }}" alt="" class="absolute inset-0 size-full object-cover opacity-45" onerror="this.remove()">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-950 via-brand-950/80 to-brand-950/20"></div>
                    <div class="relative px-6 py-8 sm:px-8">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-gold-300">Home / {{ $form->title ?: 'Page' }}</p>
                        <p class="mt-3 font-display text-2xl font-semibold text-white sm:text-3xl">{{ $form->banner_title ?: ($form->title ?: 'Page title') }}</p>
                        @if ($form->excerpt)
                            <p class="mt-2 max-w-xl font-display text-base italic text-gold-100/90">{{ $form->excerpt }}</p>
                        @endif
                        @if ($form->banner_text)
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">{{ $form->banner_text }}</p>
                        @endif
                        @if ($form->banner_button_text)
                            <span class="mt-5 inline-flex rounded-full bg-gradient-to-r from-gold-400 to-gold-500 px-4 py-2 text-xs font-semibold text-brand-950">{{ $form->banner_button_text }} &rarr;</span>
                        @endif
                    </div>
                    <span class="absolute right-3 top-3 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white/70 ring-1 ring-white/15">Preview</span>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input label="Banner title" wire:model.live.debounce.400ms="form.banner_title" :placeholder="$form->title" hint="Leave empty to use the page title." class="sm:col-span-2" />
                    <x-admin.textarea label="Excerpt" wire:model.live.debounce.400ms="form.excerpt" rows="2" hint="Short line under the title; also used in listings and as the default meta description." class="sm:col-span-2" />
                    <x-admin.textarea label="Banner description" wire:model.live.debounce.400ms="form.banner_text" rows="3" hint="Optional paragraph in the banner." class="sm:col-span-2" />
                    <x-admin.input label="Button text" wire:model.live.debounce.400ms="form.banner_button_text" placeholder="e.g. Register now" />
                    <x-admin.input label="Button link" wire:model.live.blur="form.banner_button_url" placeholder="/contact-us, https://…, #enquiry, #register" />
                    <div class="sm:col-span-2">
                        <p class="block text-sm font-medium text-slate-700">Banner image</p>
                        <div class="mt-1.5 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <input type="file" wire:model="image" accept="image/*" aria-label="Banner image"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                            @if ($current && ! $image)
                                <x-admin.toggle label="Use the default banner" wire:model.live="removeImage" class="shrink-0" />
                            @endif
                        </div>
                        <div wire:loading wire:target="image" class="mt-2 text-xs text-slate-500">Uploading…</div>
                        <p class="mt-1.5 text-xs text-slate-500">Wide photo, at least 1920 × 600 px.{{ $current ? '' : ' Without one, the default banner from Settings is used.' }}</p>
                        @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Content" description="Write in Markdown: ## headings, **bold**, *italic*, - lists, [links](https://…).">
                <x-slot:actions>
                    <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
                        <button type="button" wire:click="$set('tab', 'write')" @class(['rounded-md px-3 py-1.5', 'bg-white text-slate-900 shadow-sm' => $tab === 'write', 'text-slate-500' => $tab !== 'write'])>Write</button>
                        <button type="button" wire:click="$set('tab', 'preview')" @class(['rounded-md px-3 py-1.5', 'bg-white text-slate-900 shadow-sm' => $tab === 'preview', 'text-slate-500' => $tab !== 'preview'])>Preview</button>
                    </div>
                </x-slot:actions>

                @if ($canManage)
                    <div x-data="{ open: @js($errors->has('importUrl')) }" class="mb-5 rounded-xl border border-dashed border-gold-300/70 bg-gold-50/40">
                        <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm">
                            <span class="flex items-center gap-2.5 font-medium text-slate-800">
                                <x-admin.icon name="globe" class="size-4 text-gold-600" />
                                Copy text from a web page
                                <span class="hidden font-normal text-slate-500 sm:inline">— e.g. the same page on iiprof.com</span>
                            </span>
                            <x-admin.icon name="chevron-down" class="size-4 text-slate-400 transition" x-bind:class="open && 'rotate-180'" />
                        </button>
                        <div x-show="open" x-collapse x-cloak>
                            <div class="flex flex-col gap-3 border-t border-gold-200/60 px-4 pb-4 pt-3 sm:flex-row sm:items-start">
                                <x-admin.input wire:model="importUrl" type="url" class="flex-1" placeholder="https://iiprof.com/about-us" aria-label="Web address to copy from" wire:keydown.enter.prevent="importFromUrl" />
                                <x-admin.button type="button" variant="secondary" wire:click="importFromUrl" wire:loading.attr="disabled" wire:target="importFromUrl" class="shrink-0">
                                    <span wire:loading.remove wire:target="importFromUrl">Fetch text</span>
                                    <span wire:loading wire:target="importFromUrl">Fetching…</span>
                                </x-admin.button>
                            </div>
                            <p class="px-4 pb-4 text-xs leading-5 text-slate-500">Replaces the text below with the page’s headings, paragraphs, lists, tables, links and images (forms and pop-ups are left out). Nothing is saved until you save the page.</p>
                        </div>
                    </div>
                    @if ($importNote)
                        <p class="mb-4 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700" wire:key="import-note">
                            <x-admin.icon name="check" class="size-4" /> {{ $importNote }}
                        </p>
                    @endif
                @endif

                <div @class(['hidden' => $tab !== 'write'])>
                    <x-admin.textarea wire:model="form.content" rows="18" class="[&_textarea]:font-mono [&_textarea]:text-[13px] [&_textarea]:leading-6" placeholder="## Heading&#10;&#10;Your content…" />
                </div>
                @if ($tab === 'preview')
                    <article class="prose prose-slate max-w-none prose-headings:tracking-tight prose-a:text-brand-600 prose-code:rounded prose-code:bg-slate-100 prose-code:px-1 prose-code:before:content-none prose-code:after:content-none">{!! $this->preview !!}</article>
                @endif
            </x-admin.card>

            <x-admin.card title="SEO" description="Optional. Falls back to the title and excerpt.">
                <div class="space-y-5">
                    <x-admin.input label="Meta title" wire:model="form.meta_title" />
                    <x-admin.textarea label="Meta description" wire:model="form.meta_description" rows="2" />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Publish">
                <div class="space-y-5">
                    <x-admin.select label="Status" wire:model="form.status" :options="$statuses" />
                    <x-admin.input label="Publish date" type="datetime-local" wire:model="form.published_at" hint="Leave empty to use the moment it is published." />
                    @if ($canManage)
                        <x-admin.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save,image">
                            <span wire:loading.remove wire:target="save">{{ $form->page ? 'Save page' : 'Create page' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </x-admin.button>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Navigation">
                <div class="space-y-5">
                    <x-admin.toggle label="Show in menu" description="Adds the page to the website navigation." wire:model="form.show_in_menu" />
                    <x-admin.input label="Menu order" type="number" min="0" wire:model="form.menu_order" hint="Lower numbers appear first." />
                </div>
            </x-admin.card>


        </div>
    </fieldset>
    </form>
</div>
