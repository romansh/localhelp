<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// ─── Auth (Google OAuth) ─────────────────────────────────
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');

// ─── Locale ──────────────────────────────────────────────
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, config('localhelp.locale.available', ['en']))) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return redirect()->back();
})->name('locale.switch');

// ─── Main App ────────────────────────────────────────────
Route::get('/', App\Livewire\MapView::class)->name('home');
