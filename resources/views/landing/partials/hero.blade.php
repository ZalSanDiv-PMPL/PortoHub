<section class="relative overflow-hidden pt-12 pb-24">
    <div class="mx-auto grid max-w-7xl gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8 items-center">
        <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold tracking-[0.05em] text-emerald-600 mb-8">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                DIVERIFIKASI DAN DIPERCAYA OLEH 500+ PERUSAHAAN
            </div>

            <h1 class="text-5xl font-extrabold tracking-tight text-slate-900 sm:text-6xl lg:text-[5rem] leading-[1.1]">
                Ubah <span class="text-blue-800">Keahlian</span> Menjadi <br/><span class="text-blue-800">Peluang</span>.
            </h1>

            <p class="mt-6 max-w-lg text-lg leading-relaxed text-slate-600">
                Bangun portofolio profesional yang dilirik oleh industri. Setiap karya tervalidasi dan didukung resmi oleh guru pembimbing Anda.
            </p>

            <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md bg-blue-800 px-8 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                    Buat Portofoliomu
                </a>
                <a href="#gallery" class="inline-flex items-center justify-center rounded-md bg-blue-100 px-8 py-3 text-sm font-bold text-blue-800 transition hover:bg-blue-200">
                    Jelajahi Galeri
                </a>
            </div>
        </div>

        <div class="relative">
            <div class="grid grid-cols-2 gap-4">
                @php
                    $aspectRatios = ['aspect-[4/5]', 'aspect-square', 'aspect-video', 'aspect-[3/4]'];
                @endphp
                <div class="space-y-4 translate-y-8">
                    @for($i = 0; $i < 2; $i++)
                        @php $proj = $featuredProjects[$i] ?? null; @endphp
                        <div class="rounded-xl bg-white p-2 shadow-xl shadow-slate-200/50">
                            @if($proj && $proj->thumbnail_path)
                                <img src="{{ asset('storage/' . $proj->thumbnail_path) }}" alt="{{ $proj->title }}" class="w-full rounded-lg object-cover {{ $aspectRatios[$i] }}" />
                            @else
                                <div class="w-full rounded-lg bg-slate-100 flex items-center justify-center {{ $aspectRatios[$i] }}">
                                    <span class="text-slate-400 text-sm">No Image</span>
                                </div>
                            @endif
                            <p class="mt-2 text-xs font-semibold text-slate-600 px-2 pb-1 truncate" title="{{ $proj ? $proj->title : 'Proyek Kosong' }}">{{ $proj ? $proj->title : 'Menunggu Proyek...' }}</p>
                        </div>
                    @endfor
                </div>
                <div class="space-y-4 -translate-y-4">
                    @for($i = 2; $i < 4; $i++)
                        @php $proj = $featuredProjects[$i] ?? null; @endphp
                        <div class="rounded-xl bg-white p-2 shadow-xl shadow-slate-200/50">
                            @if($proj && $proj->thumbnail_path)
                                <img src="{{ asset('storage/' . $proj->thumbnail_path) }}" alt="{{ $proj->title }}" class="w-full rounded-lg object-cover {{ $aspectRatios[$i] }}" />
                            @else
                                <div class="w-full rounded-lg bg-slate-100 flex items-center justify-center {{ $aspectRatios[$i] }}">
                                    <span class="text-slate-400 text-sm">No Image</span>
                                </div>
                            @endif
                            <p class="mt-2 text-xs font-semibold text-slate-600 px-2 pb-1 truncate" title="{{ $proj ? $proj->title : 'Proyek Kosong' }}">{{ $proj ? $proj->title : 'Menunggu Proyek...' }}</p>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</section>
