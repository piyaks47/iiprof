<?php

namespace App\Providers;

use App\Models\Module;
use App\Models\User;
use App\Support\Access;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    public function boot(): void
    {
        // Behind cPanel/Apache the scheme can be lost on rewrites; trust APP_URL in production.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // view-{area} / manage-{area} for every admin area, from Admin → Role permissions (see App\Support\Access).
        foreach (array_merge(array_keys(Access::AREAS), Access::ADMIN_ONLY) as $area) {
            Gate::define("view-{$area}", fn (User $user) => Access::canView($user, $area));
            Gate::define("manage-{$area}", fn (User $user) => Access::canManage($user, $area));
        }
        // Modules: by role + user type (see User::availableModules()).
        Gate::define('use-module', fn (User $user, Module $module) => $user->canUseModule($module));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('enquiries', fn (Request $request) => [
            Limit::perMinute(5)->by('enquiry-m:'.$request->ip()),
            Limit::perDay(30)->by('enquiry-d:'.$request->ip()),
        ]);

        View::composer('layouts.*', function ($view) {
            $view->with('siteName', app(SiteSettings::class)->get('site_name', config('app.name')));
        });
    }
}
