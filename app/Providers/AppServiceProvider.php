<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Models\CateringPackage;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Plant;
use App\Models\User;
use App\Support\Otp\LogOtpSender;
use App\Support\PageContent;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        // Site settings and page text are read once per request; start fresh for the next one
        // (matters for long-running workers and for tests that make several requests).
        Events::listen(RequestHandled::class, fn () => $this->app->forgetScopedInstances());

        // Console CMS routes resolve their records by parameter name (the shared CmsController
        // reads them from the route), so bind them explicitly.
        Route::model('event', Event::class);
        Route::model('notice', Notice::class);
        Route::model('plant', Plant::class);
        Route::model('package', CateringPackage::class);
        Route::model('committee', CommitteeMember::class);

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
