<?php

namespace App\Livewire\Admin\Modules;

use App\Models\Module;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Landing page of a module for the people who were given it (/admin/m/{key}).
 * Module-specific features (job board, loan desk, appointments …) plug in here.
 */
#[Layout('layouts.admin')]
class Workspace extends Component
{
    public Module $module;

    public function mount(Module $module): void
    {
        abort_unless($module->is_active, 404);
        $this->authorize('use-module', $module);
        $this->module = $module;
    }

    public function render(): View
    {
        $user = auth()->user()->loadMissing('userType', 'userSubtype');
        $types = $this->module->userTypes()->active()->ordered()->with(['subtypes' => fn ($q) => $q->where('is_active', true)])->get();

        return view('livewire.admin.modules.workspace', [
            'user' => $user,
            'types' => $types,
            'viaRole' => $this->module->isForRole($user->role),
            'viaType' => $user->userType && $types->contains('id', $user->user_type_id),
        ])->title($this->module->name);
    }
}
