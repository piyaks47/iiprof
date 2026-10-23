<?php

use App\Admin\Registry;
use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes (Laravel + Livewire admin panel)
|--------------------------------------------------------------------------
| On the server, "/" is served by the React build in public_html/frontend
| (see the root .htaccess). Laravel only receives /admin, /api, /livewire-*,
| /build, /storage and /up. The redirect below is a local-dev convenience.
*/

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::livewire('/login', Admin\Auth\Login::class)->name('login');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        // Everyone: own dashboard (admin / editor / user view), profile and assigned modules.
        Route::livewire('/', Admin\Dashboard::class)->name('dashboard');
        Route::livewire('/profile', Admin\Profile::class)->name('profile');
        Route::post('/logout', LogoutController::class)->name('logout');
        Route::livewire('/m/{module}', Admin\Modules\Workspace::class)->name('module');

        // Website pages (access level per role: Admin → Role permissions).
        Route::livewire('/pages', Admin\Pages\Index::class)->middleware('can:view-pages')->name('pages.index');
        Route::livewire('/pages/create', Admin\Pages\Edit::class)->middleware('can:manage-pages')->name('pages.create');
        Route::livewire('/pages/{page}/edit', Admin\Pages\Edit::class)->middleware('can:view-pages')->name('pages.edit');

        // Homepage sections, menu, announcements, footer, enquiries … (App\Admin\Registry).
        // Each screen checks view-/manage-{area} of its resource.
        Route::prefix('manage/{resource}')->name('resources.')->where(['resource' => Registry::routePattern()])->group(function () {
            Route::livewire('/', Admin\Resources\Index::class)->name('index');
            Route::livewire('/create', Admin\Resources\Edit::class)->name('create');
            Route::livewire('/{id}/edit', Admin\Resources\Edit::class)->whereNumber('id')->name('edit');
        });

        Route::livewire('/settings', Admin\Settings::class)->middleware('can:view-settings')->name('settings');

        // Admins only.
        Route::middleware('can:manage-users')->group(function () {
            Route::livewire('/users', Admin\Users\Index::class)->name('users.index');
            Route::livewire('/users/create', Admin\Users\Edit::class)->name('users.create');
            Route::livewire('/users/{user}/edit', Admin\Users\Edit::class)->name('users.edit');
        });

        Route::middleware('can:manage-user-types')->group(function () {
            Route::livewire('/user-types', Admin\UserTypes\Index::class)->name('user-types.index');
            Route::livewire('/user-types/create', Admin\UserTypes\Edit::class)->name('user-types.create');
            Route::livewire('/user-types/{userType}/edit', Admin\UserTypes\Edit::class)->name('user-types.edit');
        });

        Route::middleware('can:manage-modules')->group(function () {
            Route::livewire('/modules', Admin\Modules\Index::class)->name('modules.index');
            Route::livewire('/modules/create', Admin\Modules\Edit::class)->name('modules.create');
            Route::livewire('/modules/{module:id}/edit', Admin\Modules\Edit::class)->name('modules.edit');
        });

        Route::livewire('/permissions', Admin\Permissions::class)->middleware('can:manage-permissions')->name('permissions');
    });
});
