<?php

use App\Http\Controllers\Auth\GitHubAppAuthController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProjectController;
use App\Livewire\Public\StudentProfile;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::view('/gallery', 'gallery')->name('gallery');

Route::get('/documentation/{documentation}', [DocumentationController::class, 'download'])
    ->name('documentation.download');



Route::get('/student/{id}', StudentProfile::class)->name('student.profile');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'role:admin,teacher,student'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    \Livewire\Volt\Volt::route('projects/create', 'project.create')->name('projects.create');
    \Livewire\Volt\Volt::route('projects/{project}/manage', 'project.manage')->name('projects.manage');
});

Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('project.show');

require __DIR__ . '/auth.php';

// GitHub App OAuth
Route::get('auth/github-app', [GitHubAppAuthController::class, 'redirectToProvider'])->name('github.redirect');
Route::get('auth/github-app/callback', [GitHubAppAuthController::class, 'handleProviderCallback'])->name('github.callback');
Route::get('auth/github-app/link', [GitHubAppAuthController::class, 'redirectToProviderLink'])->middleware('auth')->name('github.link');
Route::post('auth/github-app/unlink', [GitHubAppAuthController::class, 'unlinkProvider'])->middleware('auth')->name('github.unlink');
