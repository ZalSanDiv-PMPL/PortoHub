<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }


        if ($this->avatar) {
            $validatedAvatar = $this->validate([
                'avatar' => ['image', 'max:2048'],
            ]);

            // Hapus avatar lama jika ada
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $this->avatar->store('avatars', 'public');
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        $this->avatar = null;
        $this->dispatch('profile-updated', name: 'Avatar removed');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            {{ __('Informasi Dasar') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __("Perbarui nama, email, dan foto profil akun Anda.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        
        <!-- Avatar Section -->
        <div class="flex items-center gap-6">
            <div class="relative h-20 w-20 rounded-full overflow-hidden bg-slate-100 border border-slate-200 shadow-sm">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="h-full w-full object-cover">
                @else
                    <x-avatar :url="auth()->user()->avatar_url" :name="auth()->user()->name" />
                @endif
                <div class="absolute inset-0 bg-slate-900/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                    <label for="avatar" class="cursor-pointer p-2">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </label>
                </div>
            </div>
            <div class="flex-1">
                <input type="file" id="avatar" wire:model="avatar" class="hidden" accept="image/*">
                <div class="flex gap-3">
                    <label for="avatar" class="cursor-pointer inline-flex items-center px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        {{ __('Ubah Foto') }}
                    </label>
                    @if (auth()->user()->avatar_path)
                        <button type="button" wire:click="removeAvatar" class="inline-flex items-center px-4 py-2.5 bg-rose-50 border border-rose-200 rounded-xl text-sm font-semibold text-rose-600 shadow-sm hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition">
                            {{ __('Hapus') }}
                        </button>
                    @endif
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </div>
        </div>

        <div>
            <label for="name" class="block text-sm font-semibold text-slate-700">{{ __('Nama Lengkap') }}</label>
            <input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700">{{ __('Email') }}</label>
            <input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-slate-100">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                <span wire:loading.remove wire:target="updateProfileInformation">{{ __('Simpan Perubahan') }}</span>
                <span wire:loading.inline-flex wire:target="updateProfileInformation" class="items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Menyimpan...
                </span>
            </button>

            <x-action-message class="me-3 text-sm font-medium text-emerald-600" on="profile-updated">
                {{ __('Berhasil disimpan.') }}
            </x-action-message>
        </div>
    </form>
</section>
