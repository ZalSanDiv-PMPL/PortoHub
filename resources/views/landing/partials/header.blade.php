<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <p class="text-2xl font-bold tracking-tight text-blue-800">PortoHub</p>
            </a>

            <nav class="hidden items-center gap-6 lg:flex" aria-label="Primary">
                <a href="{{ route('gallery') }}"
                    class="text-sm font-medium transition {{ request()->routeIs('gallery') ? 'text-blue-700 font-bold' : 'text-slate-500 hover:text-blue-700' }}">Galeri</a>
                @auth
                @if(auth()->user()->isStudent() && auth()->user()->student)
                <a href="{{ route('student.profile', auth()->user()->student->id) }}"
                    class="text-sm font-medium transition {{ request()->routeIs('student.profile') ? 'text-blue-700 font-bold' : 'text-slate-500 hover:text-blue-700' }}">Portofolioku</a>
                @endif
                <a href="{{ route('dashboard') }}"
                    class="text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'text-blue-700 border-b-2 border-blue-700 pb-1' : 'text-slate-500 hover:text-blue-700' }}">Dasbor</a>
                @else
                <a href="{{ route('home') }}#testimoni"
                    class="text-sm font-medium text-slate-500 transition hover:text-blue-700">Testimoni</a>
                @endauth
            </nav>
        </div>

        <div class="flex items-center gap-4">
            @auth
            <div class="relative hidden sm:block">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                    class="block w-full rounded-md border-0 bg-slate-200/70 py-1.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                    placeholder="Cari info...">
            </div>

            <button type="button" class="text-slate-400 hover:text-slate-500">
                <span class="sr-only">View notifications</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </button>

            @if(auth()->user()->isStudent())
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center rounded-md bg-blue-800 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                Unggah Proyek
            </a>
            @endif

            <div class="relative inline-block text-left" id="profile-dropdown-container">
                <button type="button" id="profile-menu-button"
                    class="flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <span class="sr-only">Open user menu</span>
                    <x-avatar :url="auth()->user()->avatar_url" :name="auth()->user()->name"
                        class="h-8 w-8 rounded-full object-cover" />
                </button>

                <div id="profile-menu"
                    class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-xl bg-white py-2 shadow-lg ring-1 ring-slate-900/5 focus:outline-none"
                    role="menu">
                    <div class="px-4 py-2 border-b border-slate-100 mb-1">
                        <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('profile') }}"
                        class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 hover:text-blue-700 transition"
                        role="menuitem">Profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 hover:text-blue-700 transition"
                            role="menuitem">Keluar</button>
                    </form>
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
            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 transition hover:text-blue-700">
                Masuk
            </a>
            @endif
            @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="inline-flex items-center justify-center rounded-md bg-blue-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Daftar
            </a>
            @endif
            @endauth
        </div>
    </div>
</header>