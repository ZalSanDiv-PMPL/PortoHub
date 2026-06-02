<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $rules = [
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ];

            if (Auth::user()?->hasLocalPassword()) {
                $rules['current_password'] = ['required', 'string', 'current_password'];
            }

            $validated = $this->validate($rules);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            {{ __('Ubah Kata Sandi') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ Auth::user()?->hasLocalPassword() ? __('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.') : __('Atur kata sandi agar Anda tetap bisa login meskipun koneksi GitHub terputus.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        @if(Auth::user()?->hasLocalPassword())
        <div>
            <label for="update_password_current_password" class="block text-sm font-semibold text-slate-700">{{ __('Kata Sandi Saat Ini') }}</label>
            <input wire:model="current_password" id="update_password_current_password" name="current_password"
                type="password" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>
        @endif

        <div>
            <label for="update_password_password" class="block text-sm font-semibold text-slate-700">{{ __('Kata Sandi Baru') }}</label>
            <input wire:model="password" id="update_password_password" name="password" type="password"
                class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-semibold text-slate-700">{{ __('Konfirmasi Kata Sandi') }}</label>
            <input wire:model="password_confirmation" id="update_password_password_confirmation"
                name="password_confirmation" type="password" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                <span wire:loading.remove wire:target="updatePassword">{{ __('Simpan Kata Sandi') }}</span>
                <span wire:loading.inline-flex wire:target="updatePassword" class="items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Menyimpan...
                </span>
            </button>

            <x-action-message class="me-3 text-sm font-medium text-emerald-600" on="password-updated">
                {{ __('Berhasil disimpan.') }}
            </x-action-message>
        </div>
    </form>
</section>