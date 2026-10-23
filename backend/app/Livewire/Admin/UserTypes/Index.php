<?php

namespace App\Livewire\Admin\UserTypes;

use App\Models\UserType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('User types')]
class Index extends Component
{
    #[Url(except: '')]
    public string $search = '';

    public function toggleActive(UserType $userType): void
    {
        $userType->update(['is_active' => ! $userType->is_active]);
        $this->dispatch('notify', message: "{$userType->name} ".($userType->is_active ? 'activated.' : 'deactivated — its members lose its modules.'));
    }

    public function delete(UserType $userType): void
    {
        $name = $userType->name;
        $userType->delete(); // subtypes cascade; users keep their account with no type

        $this->dispatch('notify', message: "User type {$name} deleted.");
    }

    public function render(): View
    {
        $term = trim($this->search);

        return view('livewire.admin.user-types.index', [
            'types' => UserType::query()
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->with('subtypes')
                ->withCount(['users', 'modules'])
                ->ordered()
                ->get(),
        ]);
    }
}
