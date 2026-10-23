<?php

namespace App\Livewire\Forms;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PageForm extends Form
{
    public ?Page $page = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    public string $status = 'draft';

    public bool $show_in_menu = false;

    public int $menu_order = 0;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $published_at = '';

    public string $public_path = '';

    public string $banner_title = '';

    public string $banner_text = '';

    public string $banner_button_text = '';

    public string $banner_button_url = '';

    public function setPage(Page $page): void
    {
        $this->page = $page;
        $this->fill([
            'title' => $page->title,
            'slug' => $page->slug,
            'excerpt' => (string) $page->excerpt,
            'content' => (string) $page->content,
            'status' => $page->status->value,
            'show_in_menu' => $page->show_in_menu,
            'menu_order' => $page->menu_order,
            'meta_title' => (string) $page->meta_title,
            'meta_description' => (string) $page->meta_description,
            'published_at' => $page->published_at?->format('Y-m-d\TH:i') ?? '',
            'public_path' => (string) $page->public_path,
            'banner_title' => (string) $page->banner_title,
            'banner_text' => (string) $page->banner_text,
            'banner_button_text' => (string) $page->banner_button_text,
            'banner_button_url' => (string) $page->banner_button_url,
        ]);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'not_regex:/^livewire/',
                Rule::notIn(Page::RESERVED_SLUGS),
                Rule::unique(Page::class, 'slug')->ignore($this->page?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'max:200000'],
            'status' => ['required', Rule::enum(PageStatus::class)],
            'show_in_menu' => ['boolean'],
            'menu_order' => ['integer', 'min:0', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'public_path' => ['nullable', 'string', 'max:255', 'regex:/^\/[a-z0-9]+(?:[-\/][a-z0-9]+)*$/',
                Rule::unique(Page::class, 'public_path')->ignore($this->page?->id)],
            'banner_title' => ['nullable', 'string', 'max:255'],
            'banner_text' => ['nullable', 'string', 'max:1000'],
            'banner_button_text' => ['nullable', 'string', 'max:80', 'required_with:banner_button_url'],
            'banner_button_url' => ['nullable', 'string', 'max:500', 'required_with:banner_button_text',
                'regex:/^(https?:\/\/[^\s]+|\/[^\s]*|#[^\s]*|mailto:[^\s]+|tel:[^\s]+)$/i'],
        ];
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers and single dashes only.',
            'slug.not_in' => 'This URL is reserved by the system. Please choose another slug.',
            'slug.not_regex' => 'This URL is reserved by the system. Please choose another slug.',
            'public_path.regex' => 'Use a path like /industry/banks-nbfc (lowercase letters, numbers, dashes and slashes).',
            'banner_button_url.regex' => 'Use a page path like /contact-us, a full https:// link, #enquiry or #register.',
        ];
    }

    public function save(?UploadedFile $image = null, bool $removeImage = false): Page
    {
        $data = $this->validate();

        foreach (['excerpt', 'content', 'meta_title', 'meta_description', 'published_at', 'public_path',
            'banner_title', 'banner_text', 'banner_button_text', 'banner_button_url'] as $key) {
            $data[$key] = filled($data[$key]) ? $data[$key] : null;
        }

        $page = $this->page ?? new Page(['author_id' => auth()->id()]);
        $page->fill($data);

        if (($removeImage || $image) && $page->featured_image) {
            if (\App\Support\Media\Files::owned($page->featured_image)) {
                Storage::disk('public')->delete($page->featured_image);
            }
            $page->featured_image = null;
        }

        if ($image) {
            $page->featured_image = $image->store('pages', 'public');
        }

        $page->save();
        $this->setPage($page);

        return $page;
    }
}
