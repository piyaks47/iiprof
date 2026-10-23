<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(as: 'type', except: '')]
    public string $userType = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedUserType(): void
    {
        $this->resetPage();
    }

    public function toggleActive(User $user): void
    {
        if ($user->is(auth()->user())) {
            $this->dispatch('notify', type: 'error', message: 'You cannot deactivate your own account.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('notify', message: $user->is_active ? "{$user->name} activated." : "{$user->name} deactivated.");
    }

    public function delete(User $user): void
    {
        if ($user->is(auth()->user())) {
            $this->dispatch('notify', type: 'error', message: 'You cannot delete your own account.');

            return;
        }

        $user->delete();
        $this->dispatch('notify', message: 'User deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->with('userType:id,name', 'userSubtype:id,name')
                ->search($this->search)
                ->when($this->role !== '', fn ($q) => $q->where('role', $this->role))
                ->when($this->userType === 'none', fn ($q) => $q->whereNull('user_type_id'))
                ->when(ctype_digit($this->userType), fn ($q) => $q->where('user_type_id', (int) $this->userType))
                ->latest()
                ->paginate(15),
            'roles' => Role::options(),
            'types' => ['none' => 'No user type'] + UserType::query()->ordered()->pluck('name', 'id')->all(),
        ]);
    }
}
