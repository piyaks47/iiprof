<?php

namespace App\Livewire\Admin\UserTypes;

use App\Models\Module;
use App\Models\UserSubtype;
use App\Models\UserType;
use App\Support\Icons;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Create / edit a user type, its subtypes (inline CRUD) and the modules its members get.
 */
#[Layout('layouts.admin')]
class Edit extends Component
{
    public ?UserType $userType = null;

    public string $name = '';

    public string $description = '';

    public string $icon = 'user';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var array<int, string> module ids (as strings, from checkboxes) */
    public array $moduleIds = [];

    /** Create mode: subtypes typed one per line. */
    public string $initialSubtypes = '';

    /** Edit mode: [subtype id => ['name' => , 'is_active' => ]] */
    public array $subtypeRows = [];

    public string $newSubtype = '';

    public function mount(?UserType $userType = null): void
    {
        if ($userType?->exists) {
            $this->userType = $userType;
            $this->fill($userType->only('name', 'icon', 'sort_order', 'is_active'));
            $this->description = (string) $userType->description;
            $this->moduleIds = $userType->modules()->pluck('modules.id')->map(fn ($id) => (string) $id)->all();
            $this->loadSubtypes();
        } else {
            $this->sort_order = (int) UserType::query()->max('sort_order') + 10;
        }
    }

    private function loadSubtypes(): void
    {
        $this->subtypeRows = $this->userType->subtypes()->get()
            ->mapWithKeys(fn (UserSubtype $s) => [$s->id => ['name' => $s->name, 'is_active' => $s->is_active]])
            ->all();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique(UserType::class, 'name')->ignore($this->userType?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', Rule::in(array_keys(Icons::OPTIONS))],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'moduleIds' => ['array'],
            'moduleIds.*' => [Rule::exists(Module::class, 'id')],
            'initialSubtypes' => ['nullable', 'string', 'max:5000'],
        ]);

        $creating = ! $this->userType;
        $type = $this->userType ?? new UserType;
        $type->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'icon' => $data['icon'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ])->save();
        $type->modules()->sync(array_map('intval', $data['moduleIds']));

        if ($creating) {
            $lines = collect(preg_split('/\R/', $this->initialSubtypes))->map(fn ($l) => trim($l))->filter()->unique()->values();
            foreach ($lines as $i => $line) {
                $type->subtypes()->create(['name' => Str::limit($line, 100, ''), 'sort_order' => ($i + 1) * 10]);
            }
        }

        session()->flash('status', $creating ? "User type {$type->name} created." : 'User type saved.');
        $this->redirectRoute('admin.user-types.edit', ['userType' => $type], navigate: true);
    }

    // --- Subtypes (edit mode) -------------------------------------------------

    private function subtype(int $id): UserSubtype
    {
        return $this->userType->subtypes()->whereKey($id)->firstOrFail();
    }

    public function addSubtype(): void
    {
        $this->validate(['newSubtype' => ['required', 'string', 'max:100',
            Rule::unique(UserSubtype::class, 'name')->where('user_type_id', $this->userType->id)]], [], ['newSubtype' => 'subtype']);

        $this->userType->subtypes()->create([
            'name' => trim($this->newSubtype),
            'sort_order' => (int) $this->userType->subtypes()->max('sort_order') + 10,
        ]);

        $this->reset('newSubtype');
        $this->loadSubtypes();
        $this->dispatch('notify', message: 'Subtype added.');
    }

    /** Inline edits auto-save: subtypeRows.{id}.name / subtypeRows.{id}.is_active */
    public function updatedSubtypeRows($value, string $key): void
    {
        [$id, $field] = array_pad(explode('.', $key), 2, null);
        $subtype = $this->subtype((int) $id);

        if ($field === 'name') {
            $this->validate(["subtypeRows.{$id}.name" => ['required', 'string', 'max:100',
                Rule::unique(UserSubtype::class, 'name')->where('user_type_id', $this->userType->id)->ignore($subtype->id)]],
                [], ["subtypeRows.{$id}.name" => 'subtype']);
            $subtype->update(['name' => trim($value)]);
        } elseif ($field === 'is_active') {
            $subtype->update(['is_active' => (bool) $value]);
        }

        $this->dispatch('notify', message: "Subtype “{$subtype->name}” saved.");
    }

    public function moveSubtype(int $id, string $direction): void
    {
        $ids = $this->userType->subtypes()->pluck('id')->all();
        $pos = array_search($id, $ids, true);
        $swap = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($pos === false || ! isset($ids[$swap])) {
            return;
        }
        [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];

        foreach ($ids as $i => $subtypeId) {
            UserSubtype::query()->whereKey($subtypeId)->where('user_type_id', $this->userType->id)->update(['sort_order' => ($i + 1) * 10]);
        }
        $this->loadSubtypes();
    }

    public function deleteSubtype(int $id): void
    {
        $subtype = $this->subtype($id);
        $subtype->delete(); // users keep their type, subtype becomes empty
        $this->loadSubtypes();
        $this->dispatch('notify', message: "Subtype “{$subtype->name}” deleted.");
    }

    public function render(): View
    {
        $title = $this->userType ? 'Edit user type' : 'New user type';

        return view('livewire.admin.user-types.edit', [
            'title' => $title,
            'icons' => Icons::OPTIONS,
            'modules' => Module::query()->ordered()->get(),
            'subtypeUserCounts' => $this->userType
                ? UserSubtype::query()->where('user_type_id', $this->userType->id)->withCount('users')->pluck('users_count', 'id')
                : collect(),
            'memberCount' => $this->userType?->users()->count() ?? 0,
        ])->title($title);
    }
}
