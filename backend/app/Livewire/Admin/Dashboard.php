<?php

namespace App\Livewire\Admin;

use App\Enums\PageStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\Module;
use App\Models\Page;
use App\Models\User;
use App\Models\UserType;
use App\Support\Dashboard\Metrics;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** One URL (/admin), three dashboards: admin, editor and user. */
#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user()->loadMissing('userType', 'userSubtype');

        return match ($user->role) {
            Role::Admin => view('livewire.admin.dashboard.admin', $this->adminData($user)),
            Role::Editor => view('livewire.admin.dashboard.editor', $this->editorData($user)),
            default => view('livewire.admin.dashboard.user', $this->userData($user)),
        };
    }

    private function adminData(User $user): array
    {
        $members = Metrics::members();

        return [
            'user' => $user,
            'kpis' => [
                ['label' => 'Members', 'value' => $members['total'], 'icon' => 'users', 'href' => route('admin.users.index'), 'trend' => $members['trend'], 'spark' => $members['spark']],
                ['label' => 'Enquiries (30 days)', 'value' => Metrics::trend(Enquiry::class)['now'], 'icon' => 'inbox', 'href' => route('admin.resources.index', 'enquiries'), 'trend' => Metrics::trend(Enquiry::class), 'spark' => Metrics::spark(Enquiry::class)],
                ['label' => 'Unread enquiries', 'value' => Enquiry::query()->unread()->count(), 'icon' => 'envelope', 'href' => route('admin.resources.index', 'enquiries')],
                ['label' => 'Registrations (30 days)', 'value' => collect(Metrics::enquiriesPerDay())->sum('registrations'), 'icon' => 'identification', 'href' => route('admin.resources.index', 'enquiries')],
                ['label' => 'Active modules', 'value' => Module::query()->active()->count(), 'icon' => 'puzzle', 'href' => route('admin.modules.index')],
                ['label' => 'User types', 'value' => UserType::query()->count(), 'icon' => 'tag', 'href' => route('admin.user-types.index')],
            ],
            ...$this->charts(),
            'content' => Metrics::content(),
            'upcoming' => Metrics::upcoming(),
            'roleCounts' => collect(Role::cases())->mapWithKeys(fn (Role $role) => [
                $role->label() => User::query()->where('role', $role)->count(),
            ]),
            'typeCounts' => UserType::query()->ordered()->withCount('users')->get()->sortByDesc('users_count')->take(8),
            'untyped' => User::query()->whereNull('user_type_id')->where('role', Role::User)->count(),
            'recentUsers' => User::query()->with('userType', 'userSubtype')->latest()->take(5)->get(),
            'recentEnquiries' => Enquiry::query()->latest()->take(5)->get(),
            'recentPages' => Page::query()->with('author:id,name')->latest('updated_at')->take(5)->get(),
        ];
    }

    /** Enquiry charts (only for people who may see enquiries). */
    private function charts(): array
    {
        if (! Gate::allows('view-enquiries')) {
            return ['perDay' => null, 'byKind' => [], 'byStatus' => [], 'topRegistrations' => collect()];
        }

        return [
            'perDay' => Metrics::enquiriesPerDay(30),
            'byKind' => Metrics::enquiriesByKind(),
            'byStatus' => Metrics::enquiriesByStatus(),
            'topRegistrations' => Metrics::topRegistrations(),
        ];
    }

    private function editorData(User $user): array
    {
        return [
            'user' => $user,
            'kpis' => array_values(array_filter([
                ['label' => 'Published pages', 'value' => Page::query()->where('status', PageStatus::Published)->count(), 'icon' => 'document', 'href' => route('admin.pages.index')],
                ['label' => 'Drafts', 'value' => Page::query()->where('status', PageStatus::Draft)->count(), 'icon' => 'pencil', 'href' => route('admin.pages.index')],
                ['label' => 'My pages', 'value' => Page::query()->where('author_id', $user->id)->count(), 'icon' => 'user'],
                Gate::allows('view-enquiries') ? ['label' => 'Unread enquiries', 'value' => Enquiry::query()->unread()->count(), 'icon' => 'envelope', 'href' => route('admin.resources.index', 'enquiries')] : null,
                Gate::allows('view-enquiries') ? ['label' => 'Enquiries (30 days)', 'value' => Metrics::trend(Enquiry::class)['now'], 'icon' => 'inbox', 'trend' => Metrics::trend(Enquiry::class), 'spark' => Metrics::spark(Enquiry::class)] : null,
            ])),
            ...$this->charts(),
            'content' => Metrics::content(),
            'upcoming' => Metrics::upcoming(),
            'recentPages' => Page::query()->with('author:id,name')->latest('updated_at')->take(6)->get(),
            'modules' => $user->availableModules(),
        ];
    }

    private function userData(User $user): array
    {
        return [
            'user' => $user,
            'modules' => $user->availableModules(),
        ];
    }
}
