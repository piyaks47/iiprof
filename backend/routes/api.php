<?php

use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PeopleController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\TrainingController;
use App\Http\Controllers\Api\V1\UserTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API v1  ->  https://piya.karmickdev.com/api/v1/...
|--------------------------------------------------------------------------
| Consumed by the React site (public_html/frontend) and, later, the React Native app.
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('/site', SiteController::class)->name('site');
    Route::get('/home', HomeController::class)->name('home');
    Route::get('/user-types', UserTypeController::class)->name('user-types');
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/page', [PageController::class, 'byPath'])->name('pages.by-path');
    Route::get('/pages/{slug}', [PageController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('pages.show');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{slug}', [EventController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('events.show');
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('news.show');
    Route::get('/seminars/{slug}', [NewsController::class, 'seminar'])->where('slug', '[a-z0-9-]+')->name('seminars.show');
    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::get('/training/{slug}', [TrainingController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('training.show');
    Route::get('/team', [PeopleController::class, 'team'])->name('team');
    Route::get('/testimonials', [PeopleController::class, 'testimonials'])->name('testimonials');
    Route::get('/gallery', [PeopleController::class, 'gallery'])->name('gallery');
    Route::post('/enquiries', [EnquiryController::class, 'store'])
        ->middleware('throttle:enquiries')
        ->name('enquiries.store');
});
