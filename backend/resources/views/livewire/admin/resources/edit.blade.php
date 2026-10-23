<div>
    <x-admin.page-header :title="$title" :back="$res->url()">
        <x-slot:actions>
            @if (! $canManage)
                <x-admin.badge color="slate"><x-admin.icon name="eye" class="size-3.5" /> View only</x-admin.badge>
            @endif
            @if ($publicUrl)
                <x-admin.button variant="secondary" icon="external" :href="$publicUrl" target="_blank">View on site</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form wire:submit="save" novalidate class="grid gap-6 lg:grid-cols-3">
        <fieldset @disabled(! $canManage) class="min-w-0 space-y-6 lg:col-span-2">
            <x-admin.card>
                @if ($fields === [])
                    <p class="text-sm text-slate-500">This section has no texts of its own. Use the switch on the right to show or hide it{{ $aside && $aside['url'] ? ', and manage its items with the link on the right' : '' }}.</p>
                @endif
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        @php
                            $key = 'data.'.$field->name;
                            $span = $field->wide ? 'sm:col-span-2' : '';
                        @endphp
                        <div class="{{ $span }}" wire:key="field-{{ $field->name }}">
                            @if ($field->readonly)
                                <p class="block text-sm font-medium text-slate-700">{{ $field->label }}</p>
                                <div class="mt-1.5 whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-800 ring-1 ring-inset ring-slate-200">{{ $data[$field->name] !== '' ? $data[$field->name] : '—' }}</div>
                            @elseif ($field->type === 'textarea')
                                <x-admin.textarea :label="$field->label" wire:model="{{ $key }}" :rows="$field->rows" :hint="$field->hint" :placeholder="$field->placeholder" :required="$field->required" />
                            @elseif ($field->type === 'markdown')
                                <div class="flex items-center justify-between">
                                    <label for="f_{{ str_replace('.', '_', $key) }}" class="block text-sm font-medium text-slate-700">{{ $field->label }}</label>
                                    <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
                                        <button type="button" wire:click="togglePreview('{{ $field->name }}')" @class(['rounded-md px-3 py-1', 'bg-white text-slate-900 shadow-sm' => $previewing !== $field->name, 'text-slate-500' => $previewing === $field->name])>Write</button>
                                        <button type="button" wire:click="togglePreview('{{ $field->name }}')" @class(['rounded-md px-3 py-1', 'bg-white text-slate-900 shadow-sm' => $previewing === $field->name, 'text-slate-500' => $previewing !== $field->name])>Preview</button>
                                    </div>
                                </div>
                                <div x-data="mdToolbar" @class(['mt-1.5', 'hidden' => $previewing === $field->name])>
                                    @if ($canManage)
                                        <div class="mb-1.5 flex flex-wrap gap-1" role="toolbar" aria-label="Formatting">
                                            @foreach ([['B', "wrap('**')", 'Bold', 'font-bold'], ['I', "wrap('*')", 'Italic', 'italic'], ['H2', "lines('## ')", 'Heading', ''], ['H3', "lines('### ')", 'Sub-heading', ''], ['• List', "lines('- ')", 'Bulleted list', ''], ['1. List', "lines('1. ')", 'Numbered list', ''], ['Link', 'link()', 'Link', 'underline'], ['“ Quote', "lines('> ')", 'Quote', '']] as [$text, $action, $title, $class])
                                                <button type="button" x-on:click="{{ $action }}" title="{{ $title }}" class="rounded-md bg-white px-2.5 py-1 text-xs text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-brand-700 {{ $class }}">{{ $text }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                    <x-admin.textarea wire:model="{{ $key }}" :rows="$field->rows" :hint="$field->hint" class="[&_textarea]:font-mono [&_textarea]:text-[13px] [&_textarea]:leading-6" />
                                </div>
                                @if ($previewing === $field->name)
                                    <article class="prose prose-slate mt-1.5 max-w-none rounded-lg p-4 ring-1 ring-slate-200 prose-a:text-brand-600">{!! $markdown($data[$field->name] ?? '') !!}</article>
                                @endif
                            @elseif ($field->type === 'toggle')
                                <div class="pt-6"><x-admin.toggle :label="$field->label" :description="$field->hint" wire:model="{{ $key }}" /></div>
                            @elseif (in_array($field->type, ['select', 'icon'], true))
                                <x-admin.select :label="$field->label" wire:model.live="{{ $key }}" :options="$field->resolveOptions($record)"
                                    :placeholder="$field->emptyOption ?? ($field->required ? null : '—')" :hint="$field->hint" />
                                @if ($field->type === 'icon' && ($data[$field->name] ?? ''))
                                    <p class="mt-2 inline-flex items-center gap-2 text-xs text-slate-500"><x-admin.icon :name="$data[$field->name]" class="size-5 text-brand-600" /> Preview</p>
                                @endif
                            @elseif ($field->type === 'image')
                                @php
                                    $upload = $uploads[$field->name] ?? null;
                                    $current = $record->exists && method_exists($record, 'imageUrl') ? $record->imageUrl($field->name) : null;
                                    $removing = ! empty($removeImage[$field->name]);
                                @endphp
                                <p class="block text-sm font-medium text-slate-700">{{ $field->label }} @if ($field->required)<span class="text-rose-500">*</span>@endif</p>
                                <div class="mt-1.5 flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <div class="w-full shrink-0 sm:w-56">
                                        @if ($upload && method_exists($upload, 'isPreviewable') && $upload->isPreviewable())
                                            <img src="{{ $upload->temporaryUrl() }}" alt="" class="aspect-video w-full rounded-lg object-cover ring-1 ring-slate-200">
                                        @elseif ($current && ! $removing)
                                            <img src="{{ $current }}" alt="" class="aspect-video w-full rounded-xl bg-slate-100 object-cover ring-1 ring-slate-200" onerror="this.style.opacity=0">
                                            @if (preg_match('~^https?://~i', (string) $record->getAttribute($field->name)))
                                                <p class="mt-1.5 text-[11px] text-slate-400">Loaded from {{ parse_url((string) $record->getAttribute($field->name), PHP_URL_HOST) }} until the images are copied to this server.</p>
                                            @endif
                                        @else
                                            <div class="flex aspect-video w-full items-center justify-center rounded-lg border-2 border-dashed border-slate-200 text-slate-400"><x-admin.icon name="photo" class="size-8" /></div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-3">
                                        @if ($canManage)
                                            <input type="file" wire:model="uploads.{{ $field->name }}" accept="image/*" aria-label="{{ $field->label }}"
                                                class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                            <div wire:loading wire:target="uploads.{{ $field->name }}" class="text-xs text-slate-500">Uploading…</div>
                                        @endif
                                        @if ($field->hint)<p class="text-xs text-slate-500">{{ $field->hint }}</p>@endif
                                        @error('uploads.'.$field->name)<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                        @if ($current && ! $upload && ! $field->required && $canManage)
                                            <x-admin.toggle label="Remove current image" wire:model.live="removeImage.{{ $field->name }}" />
                                        @endif
                                    </div>
                                </div>
                            @else
                                <x-admin.input :label="$field->label" wire:model="{{ $key }}" :hint="$field->hint" :placeholder="$field->placeholder" :required="$field->required"
                                    :type="match ($field->type) { 'number' => 'number', 'date' => 'date', 'email' => 'email', default => 'text' }" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        </fieldset>

        <div class="space-y-6">
            <x-admin.card :title="$canManage ? ($record->exists ? 'Save changes' : 'Create') : 'Details'">
                <div class="space-y-5">
                    @if ($res->toggleable())
                        <x-admin.toggle label="Shown on the website" description="Switch off to hide it without deleting." wire:model="isActive" :disabled="! $canManage" />
                    @endif
                    @if ($record->exists)
                        <dl class="space-y-1 text-xs text-slate-500">
                            <div class="flex justify-between"><dt>Created</dt><dd>{{ $record->created_at?->format('j M Y, H:i') }}</dd></div>
                            <div class="flex justify-between"><dt>Last change</dt><dd>{{ $record->updated_at?->diffForHumans() }}</dd></div>
                        </dl>
                    @endif
                    @if ($canManage)
                        <x-admin.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save,uploads">
                            <span wire:loading.remove wire:target="save">{{ $record->exists ? 'Save' : 'Create '.$res->singular() }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </x-admin.button>
                    @endif
                </div>
            </x-admin.card>

            @if ($aside)
                <x-admin.card :title="$aside['title']">
                    <p class="text-sm text-slate-600">{{ $aside['text'] }}</p>
                    @if ($aside['url'])
                        <x-admin.button variant="secondary" icon="arrow-right" :href="$aside['url']" wire:navigate class="mt-4 w-full">{{ $aside['link'] }}</x-admin.button>
                    @endif
                </x-admin.card>
            @endif

            @if ($canManage && $record->exists && $res->canDelete($record))
                <x-admin.card>
                    <x-admin.button variant="ghost-danger" icon="trash" class="w-full" wire:click="delete" wire:confirm="Delete this {{ $res->singular() }}? This cannot be undone.">
                        Delete {{ $res->singular() }}
                    </x-admin.button>
                </x-admin.card>
            @endif
        </div>
    </form>
</div>
