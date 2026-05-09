<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
    <!-- Left: Form -->
    <div class="flex items-center justify-center bg-slate-200 p-8">
        <div class="w-full max-w-md">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-6">Masuk</h1>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login" class="space-y-4">
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email"
                        required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Kata sandi" />
                    <div class="relative">
                        <input wire:model="form.password" id="password"
                            class="block mt-1 w-full pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            type="password" name="password" required autocomplete="current-password" />
                        <button type="button" id="password-toggle" onclick="togglePassword('password')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">
                            <svg id="password-toggle-icon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center">
                        <input wire:model="form.remember" id="remember" type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            name="remember">
                        <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
                    </label>

                    @if (Route::has('password.request'))
                    <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}"
                        wire:navigate>
                        Lupa password?
                    </a>
                    @endif
                </div>

                <div class="w-full">
                    <x-primary-button class="w-full justify-center bg-blue-700 focus:ring-blue-500 text-white">Masuk
                    </x-primary-button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-slate-200 px-2 text-gray-500">Atau lanjut dengan</span>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('github.redirect') }}"
                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-5 h-5 me-2" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.11.82-.26.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.757-1.333-1.757-1.09-.745.083-.73.083-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.835 2.807 1.305 3.492.998.108-.775.418-1.305.762-1.605-2.665-.305-5.466-1.335-5.466-5.93 0-1.31.468-2.382 1.236-3.222-.124-.303-.536-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.98-.399 3-.405 1.02.006 2.043.139 3 .405 2.289-1.552 3.295-1.23 3.295-1.23.655 1.653.243 2.874.12 3.176.77.84 1.235 1.912 1.235 3.222 0 4.61-2.806 5.623-5.48 5.92.43.37.823 1.102.823 2.222 0 1.606-.015 2.903-.015 3.297 0 .32.216.694.825.576C20.565 21.796 24 17.3 24 12c0-6.627-5.373-12-12-12z"
                                clip-rule="evenodd" />
                        </svg>
                        GitHub
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Illustration / Marketing -->
    <div class="hidden md:flex items-center justify-center bg-gradient-to-b from-blue-800 to-sky-900 text-white p-8">
        <div class="max-w-lg">
            <h2 class="text-5xl font-extrabold">Selamat datang kembali</h2>
            <p class="mt-6 text-lg leading-relaxed">Masuk untuk mengelola proyek, memperbarui profil, dan mengakses
                fitur profesional.</p>
            <div class="mt-8">
                <a href="{{ route('register') }}"
                    class="inline-flex w-full max-w-xs items-center justify-center px-6 py-3 bg-white/90 text-blue-800 font-semibold rounded-full hover:bg-white transition">Buat
                    akun</a>
            </div>
        </div>
    </div>
</div>
<script>
    function togglePassword(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var icon = document.getElementById(id + '-toggle-icon') || document.getElementById('password-toggle-icon');
        if (el.type === 'password') {
            el.type = 'text';
            if (icon) {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.223-3.182M6.223 6.223A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7-.162.516-.384 1.018-.657 1.494M3 3l18 18" />';
            }
        } else {
            el.type = 'password';
            if (icon) {
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />';
            }
        }
    }
</script>