<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Models\User;
use App\Support\Otp\LogOtpSender;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Gate;
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

        View::composer('components.layouts.*', function (\Illuminate\View\View $view): void {
            $view->with('site', $this->app->make(SiteSettings::class));
        });
    }
}
