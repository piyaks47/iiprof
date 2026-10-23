<?php

namespace App\Livewire\Admin\Resources;

use App\Admin\Registry;
use App\Admin\Resource;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** List screen of any admin resource: /admin/manage/{resource}. */
#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Locked]
    public string $resource = '';

    #[Url(except: '')]
    public string $search = '';

    /** @var array<string, string> */
    #[Url(except: [])]
    public array $filter = [];

    public function mount(string $resource): void
    {
        $this->resource = $resource;
        Gate::authorize('view-'.$this->definition()->area());
    }

    protected function definition(): Resource
    {
        return Registry::get($this->resource);
    }

    protected function authorizeManage(): void
    {
        Gate::authorize('manage-'.$this->definition()->area());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->filter = array_filter($this->filter, fn ($v) => $v !== '' && $v !== null);
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->authorizeManage();
        $resource = $this->definition();
        abort_unless($resource->toggleable(), 403);

        $record = $resource->find($id);
        $record->is_active = ! $record->is_active;
        $record->save();

        $this->dispatch('notify', message: '“'.$resource->titleFor($record).'” is now '.($record->is_active ? 'shown' : 'hidden').'.');
    }

    public function move(int $id, string $direction): void
    {
        $this->authorizeManage();
        $resource = $this->definition();
        abort_unless($resource->sortable(), 403);

        $record = $resource->find($id);
        $siblings = $record->siblingsQuery()->ordered()->get();

        DB::transaction(function () use ($siblings, $record, $direction) {
            $ids = $siblings->pluck('id')->all();
            $index = array_search($record->id, $ids, true);
            $swap = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! isset($ids[$swap])) {
                return;
            }

            [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];

            foreach ($ids as $position => $siblingId) {
                $sibling = $siblings->firstWhere('id', $siblingId);
                if ($sibling->sort_order !== $position + 1) {
                    $sibling->sort_order = $position + 1;
                    $sibling->saveQuietly();
                }
            }
        });
    }

    public function delete(int $id): void
    {
        $this->authorizeManage();
        $resource = $this->definition();
        $record = $resource->find($id);
        abort_unless($resource->canDelete($record), 403);

        $title = $resource->titleFor($record);
        $record->delete();

        $this->dispatch('notify', message: '“'.$title.'” deleted.');
    }

    public function render(): View
    {
        $resource = $this->definition();
        $query = $resource->query();

        foreach ($resource->filters() as $column => $filter) {
            $value = $this->filter[$column] ?? '';
            if ($value !== '' && array_key_exists($value, $filter['options'])) {
                $query->where($query->getModel()->qualifyColumn($column), $value);
            }
        }

        $term = trim($this->search);
        if ($term !== '' && $resource->searchable()) {
            $query->where(function ($q) use ($resource, $term) {
                foreach ($resource->searchable() as $column) {
                    $q->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        if ($resource->isTree()) {
            $records = MenuItem::flatTree();
        } elseif ($resource->sortable()) {
            $records = $query->get(); // hand-ordered lists are short: show them all
        } else {
            $records = $query->paginate(25);
        }

        return view('livewire.admin.resources.index', [
            'res' => $resource,
            'records' => $records,
            'canManage' => Gate::allows('manage-'.$resource->area()),
            'showMove' => $resource->sortable() && $term === '',
            'siblingBounds' => $this->siblingBounds($records, $resource),
        ])->title($resource->label());
    }

    /**
     * First / last row of every sibling group, so the up / down buttons can be disabled.
     *
     * @return array{first: array<int, true>, last: array<int, true>}
     */
    private function siblingBounds(iterable $records, Resource $resource): array
    {
        $bounds = ['first' => [], 'last' => []];
        if (! $resource->sortable()) {
            return $bounds;
        }

        $groups = [];
        foreach ($records as $record) {
            /** @var Model $record */
            $column = method_exists($record, 'sortGroupColumn') ? $record->sortGroupColumn() : null;
            $groups[$column ? (string) $record->getAttribute($column) : '_'][] = $record->getKey();
        }

        foreach ($groups as $ids) {
            $bounds['first'][reset($ids)] = true;
            $bounds['last'][end($ids)] = true;
        }

        return $bounds;
    }
}
