<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Models\User;
use App\Support\Otp\LogOtpSender;
use App\Support\PageContent;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SiteSettings::class);
        $this->app->scoped(PageContent::class);

        $this->app->bind(OtpSender::class, function (): OtpSender {
            return match (config('services.otp.driver')) {
                'log' => new LogOtpSender,
                default => throw new InvalidArgumentException('Unknown OTP driver. Only "log" is available until an SMS provider is set up.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The Admin (Secretary) role may do everything.
        Gate::before(fn (User $user): ?bool => $user->isAdmin() ? true : null);

        // Website forms: a few sends a minute from one address, and a daily cap.
        RateLimiter::for('forms', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);

        View::composer(['components.layouts.*', 'site.*', 'errors.*', 'member.*', 'admin.*'], function (\Illuminate\View\View $view): void {
            $view->with('site', $this->app->make(SiteSettings::class));
        });
    }
}
