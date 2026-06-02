<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth-split')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'student';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:student,teacher'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['password_set_at'] = now();

        $baseUsername = \Illuminate\Support\Str::slug($validated['name']);
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '-' . $counter;
            $counter++;
        }
        $validated['username'] = $username;

        $user = User::create($validated);
        $user->refresh();
        
        if ($user->role === 'student') {
            \App\Models\Student::create([
                'user_id' => $user->id,
                'nis' => null,
                'year' => null,
            ]);
        } elseif ($user->role === 'teacher') {
            \App\Models\Teacher::create([
                'user_id' => $user->id,
                'nip' => null,
                'is_validated' => false,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
    <!-- Left: Marketing / Illustration -->
    <div class="hidden md:flex items-center justify-center bg-gradient-to-b from-blue-800 to-sky-900 text-white p-8 transition-all duration-500">
        <div class="max-w-lg" x-data="{ role: @entangle('role') }">
            <h2 class="text-5xl font-extrabold" x-text="role === 'teacher' ? 'Bergabung sebagai Guru' : 'Gabung ke PortoHub'">Gabung ke PortoHub</h2>
            <p class="mt-6 text-lg leading-relaxed" x-text="role === 'teacher' ? 'Validasi dan kelola portofolio siswa dengan mudah. Bantu mereka lebih profesional dan siap untuk terjun ke industri.' : 'Daftar sekarang untuk menjadi bagian dari PortoHub dan buat proyek Anda lebih terverifikasi serta profesional.'">Daftar sekarang untuk menjadi bagian dari PortoHub dan buat proyek Anda lebih terverifikasi serta profesional.</p>
            <div class="mt-8">
                <a href="{{ route('login') }}"
                    class="inline-flex w-full max-w-xs items-center justify-center px-4 py-3 bg-white/90 text-blue-800 font-semibold rounded-full hover:bg-white transition">Sudah
                    punya akun? Masuk</a>
            </div>
        </div>
    </div>

    <!-- Right: Form -->
    <div class="flex items-center justify-center bg-white p-8">
        <div class="w-full max-w-md">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Daftar</h1>

            <form wire:submit="register" class="space-y-4">
                
                <!-- Role Toggle -->
                <div class="flex p-1 space-x-1 bg-slate-100/80 rounded-xl mb-6">
                    <button type="button" 
                        wire:click="$set('role', 'student')"
                        class="w-full rounded-lg py-2.5 text-sm font-bold leading-5 ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2 transition-all duration-200"
                        :class="$wire.role === 'student' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Sebagai Siswa
                    </button>
                    <button type="button" 
                        wire:click="$set('role', 'teacher')"
                        class="w-full rounded-lg py-2.5 text-sm font-bold leading-5 ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2 transition-all duration-200"
                        :class="$wire.role === 'teacher' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Sebagai Guru
                    </button>
                </div>
                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required
                        autofocus autocomplete="off" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email"
                        required autocomplete="off" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Kata sandi" />
                    <div class="relative">
                        <input wire:model="password" id="password"
                            class="block mt-1 w-full pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            type="password" name="password" required autocomplete="off" />
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
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Konfirmasi kata sandi" />
                    <div class="relative">
                        <input wire:model="password_confirmation" id="password_confirmation"
                            class="block mt-1 w-full pr-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            type="password" name="password_confirmation" required autocomplete="off" />
                        <button type="button" id="password_confirmation-toggle"
                            onclick="togglePassword('password_confirmation')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500">
                            <svg id="password_confirmation-toggle-icon" xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <script>
                    function togglePassword(id) {
                        var el = document.getElementById(id);
                        if (!el) return;
                        var icon = document.getElementById(id + '-toggle-icon');
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

                <div class="w-full">
                    <x-primary-button class="w-full justify-center bg-blue-700 focus:ring-blue-500 text-white">Daftar
                    </x-primary-button>
                </div>
            </form>
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-gray-500">Atau lanjut dengan</span>
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
                    <p class="text-xs text-center text-slate-500 mt-3 font-medium flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Pendaftaran via GitHub dikhususkan untuk akun Siswa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>