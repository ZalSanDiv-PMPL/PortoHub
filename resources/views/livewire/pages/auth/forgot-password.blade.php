<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', match ($status) {
                Password::INVALID_USER => 'Email tersebut tidak terdaftar di sistem kami.',
                Password::RESET_THROTTLED => 'Terlalu banyak permintaan. Coba lagi sebentar lagi.',
                default => 'Kami tidak dapat mengirim tautan reset password saat ini.',
            });

            return;
        }

        $this->reset('email');

        session()->flash('status', 'Tautan reset password telah dikirim ke email Anda.');
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Lupa password? Tidak masalah. Masukkan alamat email Anda, lalu kami akan mengirimkan tautan untuk
        mengatur ulang password.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required
                autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Kirim tautan reset password
            </x-primary-button>
        </div>
    </form>
</div>