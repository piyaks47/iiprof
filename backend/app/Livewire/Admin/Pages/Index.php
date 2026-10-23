<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Pages')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    /** '' all, 'text' pages with text, 'banner' banner-only pages (menu sections without text yet). */
    #[Url(except: '')]
    public string $kind = '';

    public function updatedKind(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function togglePublished(Page $page): void
    {
        Gate::authorize('manage-pages');
        $page->status = $page->status === PageStatus::Published ? PageStatus::Draft : PageStatus::Published;
        $page->save();

        $this->dispatch('notify', message: "“{$page->title}” is now {$page->status->value}.");
    }

    public function delete(Page $page): void
    {
        Gate::authorize('manage-pages');
        if ($page->isHome()) {
            $this->dispatch('notify', type: 'error', message: 'The home page cannot be deleted.');

            return;
        }

        $page->delete();
        $this->dispatch('notify', message: 'Page deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.pages.index', [
            'pages' => Page::query()
                ->with('author:id,name')
                ->search($this->search)
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->when($this->kind === 'text', fn ($q) => $q->whereNotNull('content')->where('content', '!=', ''))
                ->when($this->kind === 'banner', fn ($q) => $q->where(fn ($q) => $q->whereNull('content')->orWhere('content', '')))
                ->orderByRaw("CASE WHEN content IS NULL OR content = '' THEN 1 ELSE 0 END")
                ->orderBy('title')
                ->paginate(20),
            'statuses' => PageStatus::options(),
            'canManage' => Gate::allows('manage-pages'),
        ]);
    }
}
