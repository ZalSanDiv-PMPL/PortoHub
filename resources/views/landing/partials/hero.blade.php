<section class="relative overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.16),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.16),_transparent_28%)]">
    <div class="mx-auto grid max-w-7xl gap-14 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-24">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Verified and trusted by SMKN 5 Malang
            </div>

            <h1 class="mt-6 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-7xl">
                Turn <span class="text-blue-700">Skills</span> into <span class="text-blue-700">Opportunity</span>.
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                PortoHub membantu siswa RPL membangun portfolio yang profesional, terverifikasi, dan siap dilihat guru maupun industri. Semua proyek, dokumentasi, validasi, dan feedback tersimpan dalam satu alur yang rapi.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-700/25 transition hover:bg-blue-600">
                    Create Your Porto
                </a>
                <a href="#platform" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-950">
                    Explore Platform
                </a>
            </div>

            <dl class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur">
                    <dt class="text-2xl font-extrabold text-slate-950">500+</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-600">portfolio tervalidasi</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur">
                    <dt class="text-2xl font-extrabold text-slate-950">3</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-600">peran utama: siswa, guru, admin</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur">
                    <dt class="text-2xl font-extrabold text-slate-950">11</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-600">tabel inti database</dd>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm backdrop-blur">
                    <dt class="text-2xl font-extrabold text-slate-950">24/7</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-600">akses portfolio dan feedback</dd>
                </div>
            </dl>
        </div>

        <div class="relative">
            <div class="absolute -left-6 top-8 h-24 w-24 rounded-full bg-sky-300/30 blur-3xl"></div>
            <div class="absolute -right-8 bottom-10 h-28 w-28 rounded-full bg-blue-500/20 blur-3xl"></div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($projects->take(2) as $project)
                    <article class="rounded-3xl border border-white/70 bg-white p-4 shadow-[0_20px_60px_-24px_rgba(15,23,42,0.3)]">
                        <div class="aspect-[4/3] overflow-hidden rounded-2xl bg-slate-100">
                            <img src="{{ $project['image'] ?? 'https://placehold.co/800x600' }}" alt="{{ $project['title'] }} preview" class="h-full w-full object-cover" />
                        </div>
                        <div class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">{{ $project['type'] }}</p>
                            <h2 class="mt-1 text-lg font-bold text-slate-950">{{ $project['title'] }}</h2>
                            <p class="mt-2 text-xs text-slate-500">{{ $project['student_name'] }} · {{ $project['class'] }}</p>
                        </div>
                    </article>
                @endforeach

                <article class="sm:col-span-2 rounded-3xl border border-white/70 bg-slate-950 p-6 text-white shadow-[0_20px_60px_-24px_rgba(15,23,42,0.45)]">
                    <div class="flex items-center gap-4">
                        <img src="{{ $featuredProject['image'] ?? 'https://placehold.co/120x120' }}" alt="Validated portfolio preview" class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/10" />
                        <div>
                            <p class="text-sm text-slate-300">Featured project</p>
                            <h2 class="text-2xl font-bold">{{ $featuredProject['title'] ?? 'Ready for validation' }}</h2>
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-7 text-slate-300">{{ $featuredProject['summary'] ?? 'Project utama yang diangkat dari data proyek asli siswa.' }}</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Status</p>
                            <p class="mt-2 text-2xl font-bold">{{ $featuredProject['status'] ?? 'approved' }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Student</p>
                            <p class="mt-2 text-2xl font-bold">{{ $featuredProject['student_name'] ?? 'Siswa PortoHub' }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/5 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">GitHub</p>
                            <p class="mt-2 text-2xl font-bold">{{ $featuredProject['repository_name'] ?? 'Synced' }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if (!empty($featuredProject['github_url']))
                            <a href="{{ $featuredProject['github_url'] }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                View on GitHub
                            </a>
                        @endif
                        @if (!empty($featuredProject['live_demo_url']))
                            <a href="{{ $featuredProject['live_demo_url'] }}" target="_blank" rel="noreferrer" class="inline-flex items-center rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">
                                Live Demo
                            </a>
                        @endif
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
