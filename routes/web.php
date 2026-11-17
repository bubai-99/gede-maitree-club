<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\CommitteeLoginController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Member;
use App\Http\Controllers\Site;
use App\Http\Controllers\Webhooks;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Language
|--------------------------------------------------------------------------
*/

Route::get('/lang/{locale}', LocaleController::class)
    ->whereIn('locale', ['en', 'bn'])
    ->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Website
|--------------------------------------------------------------------------
*/

Route::get('/', Site\HomeController::class)->name('home');

Route::get('/events', [Site\EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [Site\EventController::class, 'show'])->name('events.show');
Route::get('/notices', [Site\NoticeController::class, 'index'])->name('notices.index');
Route::get('/notices/{notice:slug}', [Site\NoticeController::class, 'show'])->name('notices.show');

Route::controller(Site\PageController::class)->group(function () {
    Route::get('/catering', 'catering')->name('catering');
    Route::get('/nursery', 'nursery')->name('nursery');
    Route::get('/gallery', 'gallery')->name('gallery');
    Route::get('/chanda', 'chanda')->name('chanda');
    Route::get('/about', 'committee')->name('committee');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/join', 'join')->name('join');
    Route::get('/volunteer', 'volunteer')->name('volunteer');
    Route::get('/panjika', 'panjika')->name('panjika');
    Route::get('/privacy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
});

// Website forms: saved as enquiries, rate limited, with a honeypot field.
Route::controller(Site\EnquiryController::class)->middleware('throttle:forms')->name('forms.')->group(function () {
    Route::post('/contact', 'contact')->name('contact');
    Route::post('/join', 'join')->name('join');
    Route::post('/catering', 'catering')->name('catering');
    Route::post('/volunteer', 'volunteer')->name('volunteer');
    Route::post('/gallery/photos', 'photos')->name('photos');
    Route::post('/chanda/pledge', 'chanda')->name('chanda');
});

Route::get('/sitemap.xml', [Site\SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [Site\SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Sign-in: members at /app/login, committee at /admin/login
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/app/login', [MemberLoginController::class, 'create'])->name('member.login');
    Route::post('/app/login', [MemberLoginController::class, 'store'])->name('member.login.store');

    Route::post('/app/login/code', [OtpLoginController::class, 'send'])->middleware('throttle:10,1')->name('member.otp.send');
    Route::get('/app/login/code', [OtpLoginController::class, 'show'])->name('member.otp.show');
    Route::post('/app/login/code/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:10,1')->name('member.otp.verify');

    Route::get('/admin/login', [CommitteeLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [CommitteeLoginController::class, 'store'])->name('admin.login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Member app
|--------------------------------------------------------------------------
*/

Route::prefix('app')->name('member.')->middleware(['auth', 'member'])->group(function () {
    Route::get('/', Member\HomeController::class)->name('home');
    Route::post('/logout', [MemberLoginController::class, 'destroy'])->name('logout');

    // Paying dues: Razorpay Standard Checkout, or UPI to the club with a UTR (see PaymentGateway).
    Route::controller(Member\PayController::class)->prefix('pay')->name('pay')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store')->middleware('throttle:20,1')->name('.store');
        Route::get('/{payment}', 'show')->name('.show');
        Route::post('/{payment}/razorpay', 'razorpay')->middleware('throttle:20,1')->name('.razorpay');
        Route::post('/{payment}/utr', 'utr')->middleware('throttle:10,1')->name('.utr');
        Route::get('/{payment}/done', 'done')->name('.done');
    });

    Route::controller(Member\AccountController::class)->group(function () {
        Route::get('/dues', 'dues')->name('dues');
        Route::get('/receipts', 'receipts')->name('receipts.index');
        Route::get('/receipts/{payment}', 'receipt')->name('receipts.show');
        Route::get('/card', 'card')->name('card');
        Route::get('/profile', 'profile')->name('profile');
    });

    Route::controller(Member\ClubController::class)->group(function () {
        Route::get('/events', 'events')->name('events.index');
        Route::get('/events/{event}', 'event')->name('events.show');
        Route::get('/notices', 'notices')->name('notices.index');
        Route::get('/notices/{notice}', 'notice')->name('notices.show');
    });
});

/*
|--------------------------------------------------------------------------
| Committee console
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'committee'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/logout', [CommitteeLoginController::class, 'destroy'])->name('logout');

    // Website CMS (policies: everyone on the committee may look; Admin and Website editor may edit)
    Route::get('/website', Admin\WebsiteController::class)->name('website');

    $cms = [
        'events' => [Admin\EventController::class, 'event'],
        'notices' => [Admin\NoticeController::class, 'notice'],
        'plants' => [Admin\PlantController::class, 'plant'],
        'packages' => [Admin\CateringPackageController::class, 'package'],
        'committee' => [Admin\CommitteeMemberController::class, 'committee'],
    ];

    foreach ($cms as $name => [$controller, $parameter]) {
        Route::resource($name, $controller)->except('show')->parameters([$name => $parameter]);
        Route::post("/{$name}/{{$parameter}}/publish", [$controller, 'publish'])->name("{$name}.publish");
        Route::delete("/{$name}/{{$parameter}}/publish", [$controller, 'unpublish'])->name("{$name}.unpublish");
    }

    Route::get('/pages', [Admin\PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{key}', [Admin\PageController::class, 'edit'])->whereIn('key', array_keys(config('pages')))->name('pages.edit');
    Route::put('/pages/{key}', [Admin\PageController::class, 'update'])->whereIn('key', array_keys(config('pages')))->name('pages.update');

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

    Route::resource('enquiries', Admin\EnquiryController::class)->only(['index', 'show', 'update']);
    Route::get('/activity', Admin\ActivityController::class)->name('activity');

    // Fees: cash entry, online and UPI payments, dues (policies: Treasurer and Admin)
    Route::get('/collect', [Admin\CollectController::class, 'create'])->name('collect');
    Route::post('/collect', [Admin\CollectController::class, 'store'])->name('collect.store');

    Route::controller(Admin\BankImportController::class)->prefix('payments/import')->name('payments.import')->group(function () {
        Route::get('/', 'create');
        Route::post('/', 'store')->name('.store');
        Route::get('/{token}', 'show')->whereUuid('token')->name('.show');
        Route::post('/{token}', 'confirm')->whereUuid('token')->name('.confirm');
    });

    Route::controller(Admin\PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{payment}/confirm', 'confirm')->name('confirm');
        Route::post('/{payment}/reject', 'reject')->name('reject');
        Route::post('/{payment}/cancel', 'cancel')->name('cancel');
    });

    Route::controller(Admin\DueController::class)->prefix('dues')->name('dues.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/generate', 'generate')->name('generate');
        Route::post('/remind', 'remind')->name('remind');
        Route::get('/{family}', 'family')->name('family');
        Route::post('/{family}/waive', 'waive')->name('waive');
    });

    // Books (khata), receipts register, cash and bank, reports
    Route::controller(Admin\ReceiptController::class)->prefix('receipts')->name('receipts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/{payment}', 'show')->name('show');
    });

    Route::controller(Admin\KhataController::class)->prefix('khata')->name('khata.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/{entry}/reverse', 'reverse')->name('reverse');
        Route::get('/{entry}/bill', 'bill')->name('bill');
    });

    Route::get('/cash', Admin\CashController::class)->name('cash');

    Route::controller(Admin\ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/collections.csv', 'collections')->name('collections');
        Route::get('/income-expenditure.csv', 'incomeExpenditure')->name('income-expenditure');
    });
});

/*
|--------------------------------------------------------------------------
| Webhooks (no session sign-in, no CSRF token: checked by signature)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/razorpay', Webhooks\RazorpayWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.razorpay');
