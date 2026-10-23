<?php

namespace App\Livewire\Admin\Modules;

use App\Enums\Role;
use App\Models\Module;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Module access')]
class Index extends Component
{
    public function toggleActive(Module $module): void
    {
        $module->update(['is_active' => ! $module->is_active]);
        $this->dispatch('notify', message: "{$module->name} ".($module->is_active ? 'switched on.' : 'switched off for everyone.'));
    }

    public function delete(Module $module): void
    {
        $name = $module->name;
        $module->delete();
        $this->dispatch('notify', message: "Module {$name} deleted.");
    }

    public function render(): View
    {
        return view('livewire.admin.modules.index', [
            'modules' => Module::query()->ordered()->with('userTypes:id,name')->get(),
            'roleLabels' => Role::options(),
        ]);
    }
}
