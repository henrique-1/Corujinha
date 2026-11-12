<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        if ($this->app->isProduction()) {
            URL::forceRootUrl((string) config('app.url'));
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('totp', function (Request $request) {
            $key = (string) ($request->user()?->id ?? $request->session()->get('totp_auth_user_id') ?? $request->ip());

            return Limit::perMinute(5)->by('totp:'.$key.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = Str::transliterate(Str::lower((string) $request->input('email')));

            return [
                Limit::perMinute(3)->by('pwd-reset:'.$email.'|'.$request->ip()),
                Limit::perMinute(10)->by('pwd-reset-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('student-answers', function (Request $request) {
            return Limit::perMinute(120)->by('student:'.$request->session()->getId().'|'.$request->ip());
        });
    }
}
