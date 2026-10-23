<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserType;
use App\Support\DashboardImage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pick the dashboard feature image for a user (own profile, or an admin editing a user)
 * or the default image of a user type. Saves immediately.
 *
 *   <livewire:admin.dashboard-image-picker :model="$user" />
 */
class DashboardImagePicker extends Component
{
    use WithFileUploads;

    /** @var User|UserType */
    #[Locked]
    public Model $model;

    public $upload;

    public function mount(Model $model): void
    {
        $this->model = $model;
        $this->authorizeAccess();
    }

    private function column(): string
    {
        return $this->model instanceof UserType ? 'feature_image' : 'dashboard_image';
    }

    private function authorizeAccess(): void
    {
        $me = auth()->user();
        $allowed = $me && ($me->isAdmin() || ($this->model instanceof User && $this->model->is($me)));
        abort_unless($allowed && ($this->model instanceof User || $this->model instanceof UserType), 403);
    }

    private function store(?string $value, string $message): void
    {
        $this->authorizeAccess();
        $column = $this->column();
        $old = $this->model->{$column};

        if ($old !== $value) {
            DashboardImage::deleteUpload($old);
        }
        $this->model->forceFill([$column => $value])->save();

        $this->dispatch('notify', message: $message);
    }

    public function choose(string $value): void
    {
        abort_unless(array_key_exists($value, DashboardImage::presetOptions()), 422);
        $this->store($value, 'Dashboard image updated.');
    }

    public function updatedUpload(): void
    {
        $this->validate(['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);
        $path = $this->upload->store('dashboard', 'public');
        $this->reset('upload');
        $this->store($path, 'Dashboard image uploaded.');
    }

    public function useDefault(): void
    {
        $this->store(null, 'Dashboard image reset to the default.');
    }

    public function render(): View
    {
        $value = $this->model->{$this->column()};
        $isUser = $this->model instanceof User;

        return view('livewire.admin.dashboard-image-picker', [
            'value' => $value,
            'currentUrl' => $isUser ? $this->model->dashboardImageUrl() : (DashboardImage::url($value) ?? DashboardImage::url(DashboardImage::ROLE_DEFAULTS['user'])),
            'presets' => collect(DashboardImage::PRESETS)->map(fn ($label, $key) => [
                'value' => "preset:{$key}", 'label' => $label, 'url' => DashboardImage::presetUrl($key),
            ])->values(),
            'isUpload' => DashboardImage::isUpload($value),
            'fallback' => $isUser
                ? ($this->model->userType?->feature_image ? 'the “'.$this->model->userType->name.'” default' : 'the '.$this->model->role->label().' default')
                : 'the role default',
        ]);
    }
}
