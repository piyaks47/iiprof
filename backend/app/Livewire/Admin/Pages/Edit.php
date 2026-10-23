<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Livewire\Forms\PageForm;
use App\Models\Page;
use App\Support\Import\PageImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/** Create (/admin/pages/create) and edit (/admin/pages/{page}/edit). */
#[Layout('layouts.admin')]
class Edit extends Component
{
    use WithFileUploads;

    public PageForm $form;

    #[Validate('nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096')]
    public $image;

    public bool $removeImage = false;

    /** "write" or "preview" for the content editor. */
    public string $tab = 'write';

    /** Once the slug is edited by hand we stop deriving it from the title. */
    public bool $slugTouched = false;

    /** "Copy text from a web page": address to read, and what happened. */
    public string $importUrl = '';

    public ?string $importNote = null;

    public function mount(?Page $page = null): void
    {
        if (! $page?->exists) {
            Gate::authorize('manage-pages');
        }

        if ($page?->exists) {
            $this->form->setPage($page);
            $this->slugTouched = true;
        }

        $this->importUrl = $this->suggestedSource();
    }

    /** The same address on iiprof.com. */
    private function suggestedSource(): string
    {
        $path = $this->form->public_path ?: ($this->form->slug ? '/'.$this->form->slug : '');

        return $path ? app(PageImporter::class)->sourceUrl($path) : PageImporter::SOURCE.'/';
    }

    /** Fills the content (and an empty title) from a web page; nothing is saved until "Save page". */
    public function importFromUrl(PageImporter $importer): void
    {
        Gate::authorize('manage-pages');
        $this->importNote = null;
        $this->validate(['importUrl' => ['required', 'url:http,https', 'max:500']], [], ['importUrl' => 'web address']);

        try {
            $data = $importer->fetch($this->importUrl);
        } catch (RuntimeException $e) {
            $this->addError('importUrl', $e->getMessage());

            return;
        }

        if ($data['content'] === '') {
            $this->addError('importUrl', match (true) {
                (bool) $data['team'] => count($data['team']).' people found on that page — they are copied (with their biographies) by "php deploy/deploy.php pull-content". Edit them under Website → Team.',
                (bool) $data['testimonials'] => 'That page lists testimonials — edit them under Website → Testimonials.',
                default => 'No page text found at that address.',
            });

            return;
        }

        $this->form->content = $data['content'];
        if (blank($this->form->title) && $data['title']) {
            $this->form->title = $data['title'];
            $this->updatedFormTitle($data['title']);
        }
        $this->tab = 'preview';
        $this->importNote = 'Copied '.number_format(mb_strlen($data['content'])).' characters from '.parse_url($data['url'], PHP_URL_HOST)
            .'. Check the preview, then save the page.';
    }

    public function updatedFormTitle(string $value): void
    {
        if (! $this->slugTouched) {
            $this->form->slug = Str::slug($value);
        }
    }

    public function updatedFormSlug(): void
    {
        $this->slugTouched = true;
    }

    #[Computed]
    public function preview(): string
    {
        return (string) Str::markdown($this->form->content ?: '_Nothing to preview yet._', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function save(): void
    {
        Gate::authorize('manage-pages');
        $this->validateOnly('image');
        $creating = ! $this->form->page;

        $page = $this->form->save($this->image, $this->removeImage);
        $this->reset('image', 'removeImage');

        session()->flash('status', $creating ? 'Page created.' : 'Page saved.');

        $this->redirectRoute('admin.pages.edit', ['page' => $page], navigate: true);
    }

    public function render(): View
    {
        $title = $this->form->page ? 'Edit page' : 'New page';

        return view('livewire.admin.pages.edit', [
            'statuses' => PageStatus::options(),
            'title' => $title,
            'canManage' => Gate::allows('manage-pages'),
            'defaultBanner' => app(\App\Support\SiteSettings::class)->forPublic()['default_banner_url'] ?? null,
            'publicUrl' => $this->form->page && $this->form->page->status === PageStatus::Published
                ? rtrim(config('app.url'), '/').$this->form->page->path()
                : null,
        ])->title($title);
    }
}
