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
    public string $nis = '';
    public string $year = '';
    public $avatar;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        if (Auth::user()->isStudent() && Auth::user()->student) {
            $this->nis = Auth::user()->student->nis ?? '';
            $this->year = Auth::user()->student->year ?? '';
        }
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

        if ($user->isStudent() && $user->student) {
            $validatedStudent = $this->validate([
                'nis' => ['required', 'string', 'max:20', Rule::unique('students', 'nis')->ignore($user->student->id)],
                'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            ]);
            $user->student->fill($validatedStudent);
            $user->student->save();
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
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        
        <!-- Avatar Section -->
        <div class="flex items-center gap-6">
            <div class="relative h-20 w-20 rounded-full overflow-hidden bg-gray-100 border border-gray-200">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="h-full w-full object-cover">
                @else
                    <x-avatar :url="auth()->user()->avatar_url" :name="auth()->user()->name" />
                @endif
                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                    <label for="avatar" class="cursor-pointer p-2">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </label>
                </div>
            </div>
            <div class="flex-1">
                <input type="file" id="avatar" wire:model="avatar" class="hidden" accept="image/*">
                <div class="flex gap-3">
                    <label for="avatar" class="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        {{ __('Upload Photo') }}
                    </label>
                    @if (auth()->user()->avatar_path)
                        <button type="button" wire:click="removeAvatar" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 rounded-md font-semibold text-xs text-red-600 uppercase tracking-widest shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Remove') }}
                        </button>
                    @endif
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
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

        @if(auth()->user()->isStudent())
            <div class="pt-6 mt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Data Akademik') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="nis" value="NIS (Nomor Induk Siswa)" />
                        <x-text-input wire:model="nis" id="nis" name="nis" type="text" class="mt-1 block w-full" placeholder="Misal: 12345" />
                        <x-input-error class="mt-2" :messages="$errors->get('nis')" />
                    </div>
                    
                    <div>
                        <x-input-label for="year" value="Tahun Angkatan" />
                        <x-text-input wire:model="year" id="year" name="year" type="number" min="2000" class="mt-1 block w-full" placeholder="Misal: {{ date('Y') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('year')" />
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-500">
                    Pastikan NIS diisi dengan benar. Kelas akan ditentukan oleh Admin atau Guru Pembimbing Anda saat proses validasi.
                </p>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
