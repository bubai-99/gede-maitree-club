<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\CommitteeLoginController;
use App\Http\Controllers\Auth\MemberLoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Member;
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

Route::view('/', 'site.home')->name('home');

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
});

/*
|--------------------------------------------------------------------------
| Committee console
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'committee'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/logout', [CommitteeLoginController::class, 'destroy'])->name('logout');
});
