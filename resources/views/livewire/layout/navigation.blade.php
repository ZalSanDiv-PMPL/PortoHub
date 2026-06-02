<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                <p class="text-2xl font-bold tracking-tight text-blue-800">PortoHub</p>
            </a>

            <nav class="hidden items-center gap-6 lg:flex" aria-label="Primary">
                <a href="{{ route('gallery') }}" class="text-sm font-medium transition {{ request()->routeIs('gallery') ? 'text-blue-700 border-b-2 border-blue-700 pb-1' : 'text-slate-500 hover:text-blue-700' }}" wire:navigate>Galeri</a>
                @auth
                    @if(auth()->user()->isStudent() && auth()->user()->student)
                        <a href="{{ route('student.profile', ['username' => auth()->user()->username]) }}" class="text-sm font-medium transition {{ request()->routeIs('student.profile') ? 'text-blue-700 border-b-2 border-blue-700 pb-1' : 'text-slate-500 hover:text-blue-700' }}" wire:navigate>Portofolioku</a>
                    @endif
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'text-blue-700 border-b-2 border-blue-700 pb-1' : 'text-slate-500 hover:text-blue-700' }}" wire:navigate>Dasbor</a>
                @else
                    <a href="{{ route('home') }}#testimoni" class="text-sm font-medium text-slate-500 transition hover:text-blue-700">Testimoni</a>
                @endauth
            </nav>
        </div>

        <div class="flex items-center gap-4">
            @auth
                <livewire:layout.global-search />

                <livewire:layout.notifications />

                @if(auth()->user()->isStudent())
                    <a href="{{ route('projects.create') }}" class="inline-flex items-center rounded-md bg-blue-800 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700" wire:navigate>
                        Unggah Proyek
                    </a>
                @endif

                <div class="relative inline-block text-left" id="profile-dropdown-container">
                    <button type="button" id="profile-menu-button" class="flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Open user menu</span>
                        <x-avatar :url="auth()->user()->avatar_url" :name="auth()->user()->name" class="h-8 w-8 rounded-full object-cover" />
                    </button>

                    <div id="profile-menu" class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-xl bg-white py-2 shadow-lg ring-1 ring-slate-900/5 focus:outline-none" role="menu">
                        <div class="px-4 py-2 border-b border-slate-100 mb-1">
                            <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 hover:text-blue-700 transition" role="menuitem" wire:navigate>Profil</a>
                        <button type="button" wire:click="logout" class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 hover:text-blue-700 transition" role="menuitem">Keluar</button>
                    </div>
                </div>

                <script>
                    function initProfileDropdown() {
                        const btn = document.getElementById('profile-menu-button');
                        const menu = document.getElementById('profile-menu');

                        // Prevent attaching multiple listeners if called multiple times
                        if(btn && menu && !btn.dataset.initialized) {
                            btn.dataset.initialized = 'true';

                            btn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                menu.classList.toggle('hidden');
                            });

                            document.addEventListener('click', (e) => {
                                if(!menu.contains(e.target)) {
                                    menu.classList.add('hidden');
                                }
                            });
                        }
                    }

                    // Handle standard page loads
                    document.addEventListener('DOMContentLoaded', initProfileDropdown);

                    // Handle Livewire SPA navigations (wire:navigate)
                    document.addEventListener('livewire:navigated', initProfileDropdown);

                    // Fallback execution
                    initProfileDropdown();
                </script>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 transition hover:text-blue-700" wire:navigate>Masuk</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" wire:navigate>Daftar</a>
                @endif
            @endauth
        </div>
    </div>
</header>
