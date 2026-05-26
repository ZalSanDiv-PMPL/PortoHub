<?php

use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GitHubAppAuthController;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::view('/gallery', 'gallery')->name('gallery');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'role:admin,teacher,student'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';

// GitHub App OAuth
Route::get('auth/github-app', [GitHubAppAuthController::class, 'redirectToProvider'])->name('github.redirect');
Route::get('auth/github-app/callback', [GitHubAppAuthController::class, 'handleProviderCallback'])->name('github.callback');
Route::get('auth/github-app/link', [GitHubAppAuthController::class, 'redirectToProviderLink'])->middleware('auth')->name('github.link');
Route::post('auth/github-app/unlink', [GitHubAppAuthController::class, 'unlinkProvider'])->middleware('auth')->name('github.unlink');
