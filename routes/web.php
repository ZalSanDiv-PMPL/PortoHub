<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GitHubAuthController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::get('auth/github', [GitHubAuthController::class, 'redirectToProvider'])->name('github.redirect');
Route::get('auth/github/callback', [GitHubAuthController::class, 'handleProviderCallback'])->name('github.callback');
