<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Role;
use App\Livewire\Forms\UserForm;
use App\Models\Module;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Create (no route param) and edit (/admin/users/{user}/edit) in one component. */
#[Layout('layouts.admin')]
class Edit extends Component
{
    public UserForm $form;

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $this->form->setUser($user);
        }
    }

    public function updatedFormUserTypeId(): void
    {
        $this->form->user_subtype_id = '';
    }

    public function save(): void
    {
        $creating = ! $this->form->user;
        $user = $this->form->save();

        session()->flash('status', $creating ? "User {$user->name} created." : 'User updated.');

        $creating
            ? $this->redirectRoute('admin.users.edit', ['user' => $user], navigate: true)
            : $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render(): View
    {
        $title = $this->form->user ? 'Edit user' : 'New user';
        $types = UserType::query()->ordered()->with('subtypes')->get();
        $type = $types->firstWhere('id', (int) $this->form->user_type_id);
        $role = Role::tryFrom($this->form->role) ?? Role::User;

        // Live preview of the modules this user will get with the chosen role + type.
        $modules = Module::query()->active()->ordered()->with('userTypes:id')->get()
            ->filter(fn (Module $m) => $role === Role::Admin || $m->isForRole($role) || ($type?->is_active && $m->userTypes->contains('id', $type->id)));

        return view('livewire.admin.users.edit', [
            'roles' => Role::options(),
            'roleDescription' => $role->description(),
            'types' => $types->mapWithKeys(fn (UserType $t) => [$t->id => $t->name.($t->is_active ? '' : ' (inactive)')])->all(),
            'subtypes' => $type?->subtypes->pluck('name', 'id')->all() ?? [],
            'modules' => $modules,
            'isSelf' => $this->form->user?->is(auth()->user()) ?? false,
            'title' => $title,
        ])->title($title);
    }
}
