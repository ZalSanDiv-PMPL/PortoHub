<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-slate-900 leading-tight">
            {{ __('Pengaturan Profil') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Kolom Kiri: Pengaturan Akun Dasar -->
                <div class="lg:col-span-5 space-y-8">
                    <div class="p-6 sm:p-8 bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl">
                        <livewire:profile.update-profile-information-form />
                    </div>

                    <div class="p-6 sm:p-8 bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl">
                        <livewire:profile.update-password-form />
                    </div>

                    <div class="p-6 sm:p-8 bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>

                <!-- Kolom Kanan: Data Akademik & Integrasi -->
                <div class="lg:col-span-7 space-y-8">
                    <div class="p-6 sm:p-8 bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl">
                        <livewire:profile.update-academic-info-form />
                    </div>

                    <div class="p-6 sm:p-8 bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl">
                        <h3 class="text-lg font-bold text-slate-900">Integrasi GitHub</h3>
                        <p class="text-sm text-slate-500 mt-1 mb-6">Hubungkan akun GitHub Anda untuk menyinkronkan repositori dan memudahkan pengiriman proyek.</p>

                        @php $token = auth()->user()->githubToken ?? null; @endphp
                        @php $user = auth()->user(); @endphp

                        @if($token)
                            <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white/80">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-700">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm text-slate-900">Terhubung sebagai {{ $token->github_username }}</div>
                                        <div class="text-xs text-slate-500 font-medium">GitHub ID: {{ $token->github_id }}</div>
                                    </div>
                                </div>
                                
                                @if($user?->hasLocalPassword())
                                <form method="POST" action="{{ route('github.unlink') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors border border-rose-200">
                                        Putuskan
                                    </button>
                                </form>
                                @else
                                <button type="button" disabled class="inline-flex items-center px-4 py-2 text-xs font-bold text-slate-400 bg-slate-100 rounded-lg cursor-not-allowed border border-slate-200" title="Atur password terlebih dahulu">
                                    Putuskan
                                </button>
                                @endif
                            </div>

                            @if(! $user?->hasLocalPassword())
                            <div class="mt-4 rounded-xl bg-blue-50/80 p-4 border border-blue-100">
                                <div class="flex">
                                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></div>
                                    <div class="ml-3 text-sm text-blue-800">
                                        <p>Harap atur kata sandi (password) pada menu di samping kiri terlebih dahulu sebelum memutuskan koneksi GitHub. Jika tidak, Anda berisiko kehilangan akses ke akun ini.</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if(empty($token->refresh_token))
                            <div class="mt-4 rounded-xl bg-amber-50/80 p-4 border border-amber-100">
                                <div class="flex">
                                    <div class="flex-shrink-0"><svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.516 9.818c.75 1.335-.213 2.983-1.742 2.983H4.483c-1.53 0-2.492-1.648-1.742-2.983L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-.25-6a.75.75 0 00-1.5 0v3.5a.75.75 0 001.5 0V7z" clip-rule="evenodd"/></svg></div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-bold text-amber-800">Refresh token tidak tersedia</h3>
                                        <div class="mt-1 text-sm text-amber-700">
                                            <p>Akses sinkronisasi otomatis mungkin berakhir. Klik <a href="{{ route('github.link') }}" class="font-bold underline text-amber-900 hover:text-amber-700">Hubungkan Ulang GitHub</a> untuk memulihkan koneksi penuh.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @else
                            <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                                <svg class="h-12 w-12 text-slate-300 mb-3" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                                <a href="{{ route('github.link') }}" class="inline-flex items-center px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-colors shadow-sm">
                                    Hubungkan GitHub
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>