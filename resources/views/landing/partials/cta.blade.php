<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
    <div class="overflow-hidden rounded-[2rem] bg-gradient-to-r from-blue-700 via-sky-700 to-blue-900 px-6 py-10 text-white shadow-2xl shadow-blue-900/20 sm:px-10 lg:px-14">
        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-100">Siap mulai</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Bangun, validasi, dan tampilkan portfolio terbaikmu di PortoHub.</h2>
                <p class="mt-4 max-w-2xl text-base leading-8 text-blue-50/90">
                    Masuk untuk melanjutkan pengelolaan proyek, atau daftar untuk mulai membangun portfolio yang terdokumentasi dan mudah divalidasi.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-blue-800 transition hover:bg-blue-50">
                        Masuk
                    </a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl border border-white/30 bg-transparent px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Daftar sekarang
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
