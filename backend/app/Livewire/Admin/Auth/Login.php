<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use PDOException;
use RuntimeException;

#[Layout('layouts.admin-guest')]
#[Title('Sign in')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $key = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
            ]);
        }

        try {
            $authenticated = Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember);
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) {
                throw $e;
            }
            // "This password does not use the Bcrypt algorithm": the hash was edited by hand (phpMyAdmin).
            $authenticated = $this->attemptWithForeignHash($key);
        }

        if (! $authenticated) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::user();

        if (! $user->canAccessAdmin()) {
            Auth::guard('web')->logout();
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->redirectIntended(route('admin.dashboard', absolute: false));
    }

    /**
     * The stored password is not in Laravel's format, usually because it was edited in phpMyAdmin.
     * Bcrypt hashes from other tools ($2a$ / $2b$) are checked and upgraded; anything else
     * (plain text, MD5 …) is refused with instructions instead of crashing with a 500.
     */
    private function attemptWithForeignHash(string $key): bool
    {
        $user = User::query()->where('email', $this->email)->first();
        $hash = (string) $user?->getAuthPassword();

        if ($user && preg_match('/^\$2[abxy]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $hash)) {
            if (! password_verify($this->password, $hash)) {
                return false;
            }

            $user->forceFill(['password' => $this->password])->saveQuietly(); // re-hashed by the "hashed" cast
            Auth::login($user, $this->remember);
            Log::notice("Sign-in: upgraded the externally generated password hash of {$user->email}.");

            return true;
        }

        RateLimiter::hit($key);
        Log::warning("Sign-in refused for {$this->email}: the stored password is not a bcrypt hash (edited in the database?). "
            .'Reset it with: php deploy/deploy.php reset-admin   or   php artisan iiprof:admin '.$this->email);

        throw ValidationException::withMessages([
            'email' => 'This account’s password has to be reset before it can sign in. The administrator can run “php deploy/deploy.php reset-admin”.',
        ]);
    }
}
