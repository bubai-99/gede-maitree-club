<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', LocaleController::class)
    ->whereIn('locale', ['en', 'bn'])
    ->name('locale.switch');

Route::view('/', 'site.home')->name('home');
