<?php

namespace Tests\Feature;

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Pages\Edit as PageEdit;
use App\Livewire\Admin\Users\Edit as UserEdit;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSee('Sign in');
    }

    public function test_admin_can_sign_in(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'Secret#12345']);

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'Secret#12345')
            ->call('login')
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $inactive = User::factory()->editor()->create(['password' => 'Secret#12345', 'is_active' => false]);

        Livewire::test(Login::class)
            ->set('email', $inactive->email)
            ->set('password', 'Secret#12345')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_members_with_the_user_role_can_sign_in(): void
    {
        $member = User::factory()->create(['password' => 'Secret#12345']);

        Livewire::test(Login::class)
            ->set('email', $member->email)
            ->set('password', 'Secret#12345')
            ->call('login')
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($member);
    }

    public function test_editor_can_manage_pages_but_not_users_or_settings(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/admin/pages')->assertOk();
        foreach (['/admin/users', '/admin/settings', '/admin/user-types', '/admin/modules'] as $url) {
            $this->actingAs($editor)->get($url)->assertForbidden();
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::query()->create(['title' => 'Hello']);

        $this->seed(\Database\Seeders\UserTypeSeeder::class);
        $this->seed(\Database\Seeders\ModuleSeeder::class);
        $type = \App\Models\UserType::query()->first();
        $module = \App\Models\Module::query()->first();

        foreach (['/admin', '/admin/pages', '/admin/pages/create', "/admin/pages/{$page->id}/edit", '/admin/users', '/admin/users/create',
            "/admin/users/{$admin->id}/edit", '/admin/settings', '/admin/profile', '/admin/user-types', '/admin/user-types/create',
            "/admin/user-types/{$type->id}/edit", '/admin/modules', '/admin/modules/create', "/admin/modules/{$module->id}/edit", "/admin/m/{$module->key}"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_page_can_be_created_with_auto_slug(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(PageEdit::class)
            ->set('form.title', 'Our Team')
            ->assertSet('form.slug', 'our-team')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'our-team', 'status' => 'published']);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(PageEdit::class)
            ->set('form.title', 'Admin area')
            ->set('form.slug', 'admin')
            ->call('save')
            ->assertHasErrors(['form.slug' => 'not_in']);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UserEdit::class, ['user' => $admin])
            ->set('form.role', 'user')
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($admin->fresh()->is_active);
    }
}
