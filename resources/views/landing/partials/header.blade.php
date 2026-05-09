<header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="#top" class="flex items-center gap-3">
            <x-application-logo class="h-10 w-10 text-blue-700" />
            <div>
                <p class="text-lg font-extrabold tracking-tight text-blue-800">PortoHub</p>
                <p class="text-xs text-slate-500">Portfolio validation platform</p>
            </div>
        </a>

        <nav class="hidden items-center gap-8 lg:flex" aria-label="Primary">
            <a href="#platform" class="text-sm font-medium text-slate-600 transition hover:text-blue-700">Platform</a>
            <a href="#fitur" class="text-sm font-medium text-slate-600 transition hover:text-blue-700">Fitur</a>
            <a href="#proses" class="text-sm font-medium text-slate-600 transition hover:text-blue-700">Proses</a>
            <a href="#testimoni" class="text-sm font-medium text-slate-600 transition hover:text-blue-700">Testimoni</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600">
                    Dashboard
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold text-slate-600 transition hover:text-blue-700">
                        Masuk
                    </a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600">
                        Daftar
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
