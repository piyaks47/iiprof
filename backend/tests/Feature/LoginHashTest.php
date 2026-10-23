<?php

namespace Tests\Feature;

use App\Livewire\Admin\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** Passwords edited by hand in phpMyAdmin must never turn sign-in into a 500. */
class LoginHashTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithRawPassword(string $raw): User
    {
        $user = User::factory()->admin()->create(['email' => 'boss@example.com']);
        DB::table('users')->where('id', $user->id)->update(['password' => $raw]);

        return $user;
    }

    public function test_bcrypt_hash_from_another_tool_signs_in_and_is_upgraded(): void
    {
        // Online generators produce $2b$ / $2a$ hashes, which Laravel's hasher rejects.
        $user = $this->adminWithRawPassword('$2b$10$'.substr(password_hash('Secret#123', PASSWORD_BCRYPT, ['cost' => 10]), 7));

        Livewire::test(Login::class)->set('email', 'boss@example.com')->set('password', 'Secret#123')->call('login')
            ->assertHasNoErrors()->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $stored = DB::table('users')->where('id', $user->id)->value('password');
        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertTrue(Hash::check('Secret#123', $stored));
    }

    public function test_wrong_password_with_foreign_hash_is_a_normal_error(): void
    {
        $this->adminWithRawPassword('$2a$10$'.substr(password_hash('Secret#123', PASSWORD_BCRYPT, ['cost' => 10]), 7));

        Livewire::test(Login::class)->set('email', 'boss@example.com')->set('password', 'wrong-one')->call('login')
            ->assertHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_plain_text_password_is_refused_with_instructions(): void
    {
        $this->adminWithRawPassword('Secret#123');

        Livewire::test(Login::class)->set('email', 'boss@example.com')->set('password', 'Secret#123')->call('login')
            ->assertHasErrors(['email'])
            ->assertSee('reset-admin');
        $this->assertGuest();
    }

    public function test_artisan_command_creates_and_resets_admins(): void
    {
        $this->artisan('iiprof:admin', ['email' => 'New@Example.com', '--password' => 'Fresh#Pass1', '--name' => 'Nia'])
            ->expectsOutputToContain('Created administrator new@example.com')->assertSuccessful();
        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->isAdmin() && $user->is_active && Hash::check('Fresh#Pass1', $user->password));

        $user->forceFill(['is_active' => false, 'role' => 'user'])->save();
        DB::table('users')->where('id', $user->id)->update(['password' => 'plain']);
        $this->artisan('iiprof:admin', ['email' => 'new@example.com', '--password' => 'Second#Pass2'])
            ->expectsOutputToContain('Reset the password')->assertSuccessful();
        $user->refresh();
        $this->assertTrue($user->isAdmin() && $user->is_active && Hash::check('Second#Pass2', $user->password));

        $this->artisan('iiprof:admin', ['email' => 'x@example.com', '--password' => 'short'])->assertFailed();
        $this->artisan('iiprof:admin', ['email' => 'r@example.com'])->expectsOutputToContain('New password:')->assertSuccessful();
    }
}
