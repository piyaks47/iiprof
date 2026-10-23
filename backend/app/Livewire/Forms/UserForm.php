<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use App\Models\User;
use App\Models\UserSubtype;
use App\Models\UserType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'user';

    /** Select values arrive as strings ('' = none). */
    public string $user_type_id = '';

    public string $user_subtype_id = '';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role->value;
        $this->user_type_id = (string) $user->user_type_id;
        $this->user_subtype_id = (string) $user->user_subtype_id;
        $this->is_active = $user->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(Role::class)],
            'user_type_id' => ['nullable', Rule::exists(UserType::class, 'id')],
            'user_subtype_id' => ['nullable', Rule::exists(UserSubtype::class, 'id')->where('user_type_id', (int) $this->user_type_id)],
            'is_active' => ['boolean'],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    protected function messages(): array
    {
        return ['user_subtype_id.exists' => 'Choose a subtype that belongs to the selected user type.'];
    }

    public function save(): User
    {
        $data = $this->validate();

        // An admin can never lock themselves out.
        if ($this->user && $this->user->is(auth()->user())) {
            $data['role'] = Role::Admin->value;
            $data['is_active'] = true;
        }

        foreach (['phone', 'user_type_id', 'user_subtype_id'] as $key) {
            $data[$key] = $data[$key] === '' ? null : $data[$key];
        }
        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user = $this->user ?? new User;
        $user->fill($data)->save();

        $this->reset('password', 'password_confirmation');

        return $user;
    }
}
