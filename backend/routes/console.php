<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/*
| php artisan iiprof:admin admin@example.com                  → new random password, printed once
| php artisan iiprof:admin admin@example.com --password=…     → set this password
| Creates the account if it does not exist; always makes it an active administrator.
| Use this instead of editing passwords in phpMyAdmin (Laravel needs its own hash format).
*/
Artisan::command('iiprof:admin {email} {--password= : New password (min. 8 characters); random if omitted} {--name= : Display name}', function (string $email) {
    $email = Str::lower(trim($email));
    $given = (string) $this->option('password');
    $password = $given !== '' ? $given : Str::password(16, symbols: false);

    $validator = Validator::make(['email' => $email, 'password' => $password], [
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8'],
    ]);
    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    $user = User::query()->firstOrNew(['email' => $email]);
    $created = ! $user->exists;

    $user->forceFill([
        'name' => $this->option('name') ?: ($user->name ?: 'Administrator'),
        'password' => $password, // hashed by the model's "hashed" cast
        'role' => Role::Admin,
        'is_active' => true,
        'email_verified_at' => $user->email_verified_at ?? now(),
    ])->save();

    $this->info(($created ? 'Created administrator ' : 'Reset the password of administrator ').$email.'.');
    if ($given === '') {
        $this->line('New password: '.$password);
    }

    return 0;
})->purpose('Create an administrator or reset an administrator password');

/*
| php artisan iiprof:import-content            fill / replace untouched starter content with the iiprof.com content
| php artisan iiprof:import-content --force    replace menus, footer, homepage items and the imported pages again
*/
Artisan::command('iiprof:import-content {--force : Replace content even if it was edited in the admin}', function () {
    $force = (bool) $this->option('force');
    $report = $force ? \App\Support\IiprofContent::import(force: true) : \App\Support\IiprofContent::import();

    foreach ($report as $block => $result) {
        $this->line(str_pad($block, 20).$result);
    }

    // The original iiprof.com texts (shipped with the code) into pages that still have a shipped text,
    // and the bundled menu images into items without one (the menu may just have been rebuilt).
    $pages = \App\Support\IiprofPages::apply($force);
    $this->line(str_pad('page texts', 20).count(array_filter($pages, fn ($r) => str_starts_with($r, 'copied'))).' pages copied');
    $this->line(str_pad('menu images', 20).\App\Support\MenuImages::apply().' items');

    $this->info('iiprof.com content imported. Run "php artisan iiprof:localize-images" to copy the images to this server.');

    return 0;
})->purpose('Import the iiprof.com content (menus, pages, people, images)');

/*
| php artisan iiprof:pull-site                       copy the page texts from iiprof.com into pages not edited here
| php artisan iiprof:pull-site --force               … and overwrite pages edited in the admin
| php artisan iiprof:pull-site --path=/about-us      only these addresses (repeat --path for more)
| Same addresses as on this site (menu + footer); management pages also bring the people's biographies.
*/
Artisan::command('iiprof:pull-site {--force : Overwrite pages edited in the admin} {--path=* : Only these addresses}', function () {
    $importer = app(\App\Support\Import\PageImporter::class);
    $counts = ['copied' => 0, 'kept' => 0, 'failed' => 0, 'other' => 0];

    $importer->pullSite((bool) $this->option('force'), (array) $this->option('path'), function (string $path, string $result) use (&$counts) {
        $kind = match (true) {
            str_starts_with($result, 'copied'), str_starts_with($result, 'people'), str_starts_with($result, 'testimonials') => 'copied',
            str_starts_with($result, 'kept') => 'kept',
            str_starts_with($result, 'failed') => 'failed',
            default => 'other',
        };
        $counts[$kind]++;
        $this->line(str_pad($path, 38).' '.$result);
    });

    $this->info("Done: {$counts['copied']} copied, {$counts['kept']} kept, {$counts['failed']} failed, {$counts['other']} unchanged / skipped.");
    if ($counts['copied']) {
        $this->line('Run "php artisan iiprof:localize-images" to copy the images in these texts to this server.');
    }

    return $counts['failed'] && ! $counts['copied'] ? 1 : 0;
})->purpose('Copy page texts (and people biographies) from iiprof.com');

/*
| Copies the images, documents and videos still loaded from iiprof.com onto this server
| (storage/app/public/imports) and points the content at the copies. Copies that have gone missing are
| fetched again. Safe to run repeatedly; every deploy runs it.
*/
Artisan::command('iiprof:localize-images', function () {
    $report = (new \App\Support\Media\MediaLocalizer(fn (string $line) => $this->line('  '.$line)))->run();

    $this->info(sprintf('%d file(s) copied to storage/app/public/imports, %d missing copies fetched again%s.',
        $report['copied'], $report['repaired'],
        $report['failed'] ? ", {$report['failed']} could not be reached (they keep loading from iiprof.com)" : ''));
    if ($report['unrecoverable']) {
        $this->warn("{$report['unrecoverable']} image(s) are missing and cannot be fetched again — upload them again in the admin.");
    }

    return 0;
})->purpose('Copy images still loaded from iiprof.com onto this server');
