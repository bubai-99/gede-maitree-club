<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Models\CateringPackage;
use App\Models\ChandaEntry;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Models\Notice;
use App\Models\PanjikaDay;
use App\Models\Plant;
use App\Models\Review;
use App\Models\User;
use App\Payments\ManualUpiGateway;
use App\Payments\PaymentGateway;
use App\Payments\RazorpayGateway;
use App\Support\Otp\LogOtpSender;
use App\Support\PageContent;
use App\Support\PlantLists;
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
        $this->app->scoped(PlantLists::class);

        // Payments: Razorpay when its keys are set, otherwise UPI to the club's own UPI ID.
        $this->app->bind(RazorpayGateway::class, fn (): RazorpayGateway => new RazorpayGateway(
            keyId: (string) config('services.razorpay.key_id'),
            keySecret: (string) config('services.razorpay.key_secret'),
            webhookSecret: is_string(config('services.razorpay.webhook_secret')) ? config('services.razorpay.webhook_secret') : null,
            apiUrl: (string) config('services.razorpay.api_url'),
        ));
        $this->app->bind(ManualUpiGateway::class, fn (): ManualUpiGateway => new ManualUpiGateway(
            upiId: is_string(config('services.upi.id')) ? config('services.upi.id') : null,
            payeeName: is_string(config('services.upi.name')) ? config('services.upi.name') : null,
        ));
        $this->app->bind(PaymentGateway::class, fn ($app): PaymentGateway => RazorpayGateway::configured()
            ? $app->make(RazorpayGateway::class)
            : $app->make(ManualUpiGateway::class));

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
        Route::model('entry', LedgerEntry::class);
        Route::model('day', PanjikaDay::class);
        Route::model('post', NewsPost::class);
        Route::model('category', NewsCategory::class);
        Route::model('review', Review::class);

        // Console menu: the khata opens for anyone who may see at least one book.
        Gate::define('khata.open', fn (User $user): bool => $user->can('viewAny', LedgerEntry::class));
        Gate::define('reports.open', fn (User $user): bool => $user->can('viewReports', LedgerEntry::class));
        Gate::define('chanda.open', fn (User $user): bool => $user->can('viewAny', ChandaEntry::class));
        Gate::define('panjika.manage', fn (User $user): bool => $user->can('events.manage') || $user->can('website.edit'));
        Gate::define('chanda.cash', fn (User $user): bool => $user->can('receiveCash', ChandaEntry::class));

        // Gate scanner: a busy gate scans a family every few seconds; sync uploads in batches.
        RateLimiter::for('scanner', fn (Request $request): Limit => Limit::perMinute(240)->by('scan:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Razorpay webhooks: generous, but not unlimited.
        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

        // Website forms: a few sends a minute from one address, and a daily cap.
        RateLimiter::for('forms', fn (Request $request): array => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perDay(50)->by($request->ip()),
        ]);

        View::composer(['components.layouts.*', 'site.*', 'errors.*', 'member.*', 'admin.*', 'partials.*'], function (\Illuminate\View\View $view): void {
            $view->with('site', $this->app->make(SiteSettings::class));
        });
    }
}
