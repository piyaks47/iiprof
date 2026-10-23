<?php

namespace App\Livewire\Admin;

use App\Support\Access;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Admin → Role permissions: access level of editors and users per admin area. */
#[Layout('layouts.admin')]
#[Title('Role permissions')]
class Permissions extends Component
{
    /** @var array<string, array<string, string>> role => area => level */
    public array $matrix = [];

    public function mount(): void
    {
        $this->load(Access::matrix());
    }

    private function load(array $matrix): void
    {
        $this->matrix = array_map(fn (array $areas) => array_map('strval', $areas), $matrix);
    }

    protected function rules(): array
    {
        return ['matrix.*.*' => ['required', 'in:0,1,2']];
    }

    public function save(): void
    {
        Gate::authorize('manage-permissions');
        $this->validate();

        Access::save($this->matrix);
        $this->dispatch('notify', message: 'Role permissions saved. Sidebars update on the next page load.');
    }

    public function setAll(string $role, string $level): void
    {
        abort_unless(in_array($level, ['0', '1', '2'], true) && isset($this->matrix[$role]), 422);

        foreach (array_keys($this->matrix[$role]) as $area) {
            $this->matrix[$role][$area] = $level;
        }
    }

    public function restoreDefaults(): void
    {
        $defaults = [];
        foreach (Access::configurableRoles() as $role) {
            foreach (Access::AREAS as $area => $definition) {
                $defaults[$role->value][$area] = $definition[$role->value];
            }
        }

        $this->load($defaults);
        $this->dispatch('notify', message: 'Defaults restored — press Save to apply them.');
    }

    public function render(): View
    {
        return view('livewire.admin.permissions', [
            'groups' => collect(Access::AREAS)->groupBy('group', preserveKeys: true),
            'roles' => Access::configurableRoles(),
            'levels' => Access::levels(),
        ]);
    }
}
