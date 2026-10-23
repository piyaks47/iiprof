<?php

namespace App\Livewire\Admin;

use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Site settings')]
class Settings extends Component
{
    use WithFileUploads;

    /** @var array<string, ?string> */
    public array $values = [];

    public $logo;

    public $banner;

    /** Uploads for the other image settings (sign-in page), keyed by setting. */
    public array $images = [];

    /** Image settings with their own card (logo and default banner have theirs). */
    private const EXTRA_IMAGES = ['login_logo', 'login_background'];

    public function mount(SiteSettings $settings): void
    {
        $this->values = collect($settings->all())
            ->except(SiteSettings::FILE_KEYS)
            ->map(fn ($v) => (string) $v)
            ->all();
    }

    protected function rules(): array
    {
        $rules = [
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'images.login_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'images.login_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ];

        foreach (SiteSettings::schema() as $key => $field) {
            if ($field['type'] === 'image') {
                continue;
            }

            $rules["values.{$key}"] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:255'],
                'link' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/[^\s]+|\/[^\s]*|#[^\s]*|mailto:[^\s]+|tel:[^\s]+)$/i'],
                'textarea' => ['nullable', 'string', 'max:2000'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $rules['values.site_name'] = ['required', 'string', 'max:100'];

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return collect(SiteSettings::schema())
            ->mapWithKeys(fn ($field, $key) => ["values.{$key}" => strtolower($field['label']), "images.{$key}" => strtolower($field['label'])])
            ->all();
    }

    public function save(SiteSettings $settings): void
    {
        Gate::authorize('manage-settings');
        $this->validate();

        $data = $this->values;

        if ($this->logo) {
            if (\App\Support\Media\Files::owned($old = $settings->get('logo'))) {
                Storage::disk('public')->delete($old);
            }
            $data['logo'] = $this->logo->store('settings', 'public');
            $this->reset('logo');
        }

        if ($this->banner) {
            if (\App\Support\Media\Files::owned($old = $settings->get('default_banner'))) {
                Storage::disk('public')->delete($old);
            }
            $data['default_banner'] = $this->banner->store('settings', 'public');
            $this->reset('banner');
        }

        foreach (self::EXTRA_IMAGES as $key) {
            if (! empty($this->images[$key])) {
                if (\App\Support\Media\Files::owned($old = $settings->get($key))) {
                    Storage::disk('public')->delete($old);
                }
                $data[$key] = $this->images[$key]->store('settings', 'public');
            }
        }
        $this->images = [];

        $settings->set($data);

        $this->dispatch('notify', message: 'Settings saved.');
    }

    public function removeLogo(SiteSettings $settings): void
    {
        Gate::authorize('manage-settings');
        if (\App\Support\Media\Files::owned($old = $settings->get('logo'))) {
            Storage::disk('public')->delete($old);
        }

        $settings->set(['logo' => null]);
        $this->dispatch('notify', message: 'Logo removed.');
    }

    public function removeBanner(SiteSettings $settings): void
    {
        Gate::authorize('manage-settings');
        if (\App\Support\Media\Files::owned($old = $settings->get('default_banner'))) {
            Storage::disk('public')->delete($old);
        }

        $settings->set(['default_banner' => null]);
        $this->dispatch('notify', message: 'Default banner removed.');
    }

    public function removeImage(SiteSettings $settings, string $key): void
    {
        Gate::authorize('manage-settings');
        abort_unless(in_array($key, self::EXTRA_IMAGES, true), 404);
        if (\App\Support\Media\Files::owned($old = $settings->get($key))) {
            Storage::disk('public')->delete($old);
        }

        $settings->set([$key => null]);
        $this->dispatch('notify', message: SiteSettings::schema()[$key]['label'].' removed.');
    }

    public function render(): View
    {
        $logo = app(SiteSettings::class)->get('logo');

        return view('livewire.admin.settings', [
            'groups' => collect(SiteSettings::schema())
                ->reject(fn ($field) => $field['type'] === 'image')
                ->groupBy('group', preserveKeys: true),
            'canManage' => Gate::allows('manage-settings'),
            'bannerUrl' => app(SiteSettings::class)->forPublic()['default_banner_url'] ?? null,
            'extraImages' => collect(self::EXTRA_IMAGES)->mapWithKeys(fn ($key) => [$key => SiteSettings::schema()[$key] + [
                'url' => app(SiteSettings::class)->forPublic()[$key.'_url'] ?? null,
            ]])->all(),
            'logoUrl' => $logo ? (preg_match('~^https?://~i', $logo) ? $logo : Storage::disk('public')->url($logo)) : null,
        ]);
    }
}
