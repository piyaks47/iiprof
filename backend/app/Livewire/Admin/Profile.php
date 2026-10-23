<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserSubtype;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('My profile')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /** Users choose their own subtype within the user type an admin assigned. */
    public string $user_subtype_id = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->user_subtype_id = (string) $user->user_subtype_id;
    }

    public function saveProfile(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'user_subtype_id' => ['nullable', Rule::exists(UserSubtype::class, 'id')->where('user_type_id', $user->user_type_id ?? 0)],
        ]);

        $data['phone'] = $data['phone'] ?: null;
        $data['user_subtype_id'] = $data['user_subtype_id'] ?: null;
        $user->update($data);

        $this->dispatch('notify', message: 'Profile updated.');
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update(['password' => $this->password]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('notify', message: 'Password changed.');
    }

    public function render(): View
    {
        $user = auth()->user()->loadMissing('userType.subtypes');

        return view('livewire.admin.profile', [
            'user' => $user,
            'subtypes' => $user->userType?->subtypes->where('is_active', true)->pluck('name', 'id')->all() ?? [],
        ]);
    }
}
