<?php

namespace App\Providers;

use App\Support\LegacyAwareHasher;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Accept imported NukeViet {SSHA512} passwords and upgrade them to bcrypt on login.
        Event::listen(Login::class, fn (Login $event) => $event->user->forceFill(['last_login_at' => now()])->saveQuietly());

        Hash::extend('bcrypt', fn ($app) => new LegacyAwareHasher($app['config']->get('hashing.bcrypt') ?? []));
    }
}
