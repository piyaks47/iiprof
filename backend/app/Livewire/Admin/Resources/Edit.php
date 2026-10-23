<?php

namespace App\Livewire\Admin\Resources;

use App\Admin\Field;
use App\Admin\Registry;
use App\Admin\Resource;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Create / edit form of any admin resource: /admin/manage/{resource}/create, /{id}/edit. */
#[Layout('layouts.admin')]
class Edit extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $resource = '';

    #[Locked]
    public ?int $recordId = null;

    /** @var array<string, mixed> field values */
    public array $data = [];

    /** @var array<string, mixed> new image uploads, by field */
    public array $uploads = [];

    /** @var array<string, bool> remove the current image, by field */
    public array $removeImage = [];

    public bool $isActive = true;

    /** Markdown field currently shown as preview. */
    public ?string $previewing = null;

    public function mount(string $resource, ?string $id = null): void
    {
        $this->resource = $resource;
        $definition = $this->definition();

        if ($id === null) {
            abort_unless($definition->canCreate(), 404);
            Gate::authorize('manage-'.$definition->area());
            $record = $definition->newRecord();
        } else {
            Gate::authorize('view-'.$definition->area());
            $record = $definition->find($id);
            $this->recordId = (int) $record->getKey();
        }

        foreach ($definition->fields($record) as $field) {
            $this->data[$field->name] = $field->isUpload() ? null : $field->formValue($record->getAttribute($field->name));
        }

        // Prefill from the list filter, e.g. "add a link to this footer column".
        if (! $record->exists) {
            foreach (request()->query('filter', []) as $column => $value) {
                if (array_key_exists($column, $this->data) && is_scalar($value)) {
                    $this->data[$column] = (string) $value;
                }
            }
        }

        $this->isActive = $record->exists ? (bool) $record->getAttribute('is_active') : true;
    }

    protected function definition(): Resource
    {
        return Registry::get($this->resource);
    }

    protected function record(): Model
    {
        $definition = $this->definition();

        return $this->recordId ? $definition->find($this->recordId) : $definition->newRecord();
    }

    /** @return list<Field> */
    protected function fields(Model $record): array
    {
        return $this->definition()->fields($record);
    }

    protected function rules(): array
    {
        $record = $this->record();
        $rules = ['isActive' => ['boolean']];

        foreach ($this->fields($record) as $field) {
            if ($field->readonly) {
                continue;
            }

            if ($field->isUpload()) {
                $rules['uploads.'.$field->name] = $field->uploadRules();
            } else {
                $rules['data.'.$field->name] = $field->validationRules($record);
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];
        foreach ($this->fields($this->record()) as $field) {
            $attributes['data.'.$field->name] = strtolower($field->label);
            $attributes['uploads.'.$field->name] = strtolower($field->label);
        }

        return $attributes;
    }

    public function updatedUploads(mixed $value, string $key): void
    {
        $this->validateOnly('uploads.'.$key);
        $this->removeImage[$key] = false;
    }

    public function togglePreview(string $field): void
    {
        $this->previewing = $this->previewing === $field ? null : $field;
    }

    public function save(): void
    {
        $definition = $this->definition();
        Gate::authorize('manage-'.$definition->area());

        $this->validate();

        $record = $this->record();
        $creating = ! $record->exists;
        $fields = $this->fields($record);

        foreach ($fields as $field) {
            if ($field->readonly || $field->isUpload()) {
                continue;
            }
            $record->setAttribute($field->name, $field->storeValue($this->data[$field->name] ?? null));
        }

        foreach ($fields as $field) {
            if (! $field->isUpload()) {
                continue;
            }

            $upload = $this->uploads[$field->name] ?? null;
            if ($upload) {
                $record->setAttribute($field->name, $upload->store($definition->key(), 'public'));
            } elseif (! empty($this->removeImage[$field->name])) {
                $record->setAttribute($field->name, null);
            }

            if ($field->required && ! $record->getAttribute($field->name)) {
                throw ValidationException::withMessages(['uploads.'.$field->name => 'Please choose '.Str::lower($field->label).'.']);
            }
        }

        if ($definition->toggleable()) {
            $record->setAttribute('is_active', $this->isActive);
        }

        $definition->beforeSave($record, $this->data);

        // Images replaced on an existing row: the model removes the old file after saving.
        $record->save();

        $this->reset('uploads', 'removeImage');
        session()->flash('status', Str::ucfirst($definition->singular()).($creating ? ' created.' : ' saved.'));

        $this->redirectRoute('admin.resources.edit', ['resource' => $definition->key(), 'id' => $record->getKey()], navigate: true);
    }

    public function delete(): void
    {
        $definition = $this->definition();
        Gate::authorize('manage-'.$definition->area());

        $record = $this->record();
        abort_unless($record->exists && $definition->canDelete($record), 403);
        $record->delete();

        session()->flash('status', Str::ucfirst($definition->singular()).' deleted.');
        $this->redirectRoute('admin.resources.index', ['resource' => $definition->key()], navigate: true);
    }

    public function render(): View
    {
        $definition = $this->definition();
        $record = $this->record();
        $title = $record->exists
            ? ($definition->canCreate() ? 'Edit '.$definition->singular() : $definition->titleFor($record))
            : 'New '.$definition->singular();

        return view('livewire.admin.resources.edit', [
            'res' => $definition,
            'record' => $record,
            'fields' => $this->fields($record),
            'canManage' => Gate::allows('manage-'.$definition->area()),
            'title' => $title,
            'publicUrl' => $record->exists ? $definition->publicUrl($record) : null,
            'aside' => $record->exists ? $definition->aside($record) : null,
            'markdown' => fn (?string $text) => (string) Str::markdown($text ?: '_Nothing to preview yet._', ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ])->title($title.' · '.$definition->label());
    }
}
