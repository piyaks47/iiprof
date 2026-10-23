<?php

namespace App\Livewire\Admin\Modules;

use App\Enums\Role;
use App\Models\Module;
use App\Models\UserType;
use App\Support\Icons;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Create / edit a module and decide who gets it: by role and/or by user type. */
#[Layout('layouts.admin')]
class Edit extends Component
{
    public ?Module $module = null;

    public string $name = '';

    public string $key = '';

    public string $description = '';

    public string $icon = 'puzzle';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var array<int, string> */
    public array $roles = [];

    /** @var array<int, string> user type ids */
    public array $typeIds = [];

    public function mount(?Module $module = null): void
    {
        if ($module?->exists) {
            $this->module = $module;
            $this->fill($module->only('name', 'key', 'icon', 'sort_order', 'is_active'));
            $this->description = (string) $module->description;
            $this->roles = $module->roles ?? [];
            $this->typeIds = $module->userTypes()->pluck('user_types.id')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->sort_order = (int) Module::query()->max('sort_order') + 10;
        }
    }

    public function selectAllTypes(bool $all = true): void
    {
        $this->typeIds = $all ? UserType::query()->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'key' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/', Rule::unique(Module::class, 'key')->ignore($this->module?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', Rule::in(array_keys(Icons::OPTIONS))],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [Rule::enum(Role::class)],
            'typeIds' => ['array'],
            'typeIds.*' => [Rule::exists(UserType::class, 'id')],
        ], ['key.regex' => 'Use lowercase letters, numbers and underscores.']);

        $creating = ! $this->module;
        $module = $this->module ?? new Module;
        $module->fill([
            'name' => $data['name'],
            'key' => $data['key'] ?: $data['name'],
            'description' => $data['description'] ?: null,
            'icon' => $data['icon'],
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
            'roles' => $data['roles'],
        ])->save();
        $module->userTypes()->sync(array_map('intval', $data['typeIds']));

        session()->flash('status', $creating ? "Module {$module->name} created." : 'Module saved.');
        $this->redirectRoute('admin.modules.index', navigate: true);
    }

    public function render(): View
    {
        $title = $this->module ? 'Edit module' : 'New module';

        return view('livewire.admin.modules.edit', [
            'title' => $title,
            'icons' => Icons::OPTIONS,
            'roleOptions' => collect(Role::cases())->map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label(), 'description' => $r->description()]),
            'types' => UserType::query()->ordered()->withCount('users')->get(),
        ])->title($title);
    }
}
