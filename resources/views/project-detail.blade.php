<x-layouts.public>
    <x-slot name="title">{{ $project->title }} — PortoHub</x-slot>
    <x-slot name="metaDescription">{{ Str::limit($project->description, 155) }}</x-slot>

    <section class="relative py-16 overflow-hidden bg-slate-50 min-h-screen">
        <!-- Decorative -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-100/50 blur-3xl opacity-50"></div>
            <div class="absolute top-40 -left-20 w-72 h-72 rounded-full bg-indigo-100/50 blur-3xl opacity-50"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <!-- Back -->
            <a href="{{ route('gallery') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-blue-600 transition mb-8 group">
                <svg class="w-4 h-4 mr-1.5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Galeri
            </a>

            <!-- Hero Card -->
            <div class="rounded-3xl overflow-hidden bg-white shadow-xl ring-1 ring-slate-200 mb-10">
                @if($project->thumbnail_path)
                <div class="aspect-video w-full bg-gradient-to-br from-slate-100 to-slate-200 relative overflow-hidden">
                    <img src="{{ asset('storage/' . $project->thumbnail_path) }}" alt="{{ $project->title }}" class="w-full h-full object-cover" />
                </div>
                @else
                <div class="aspect-[3/1] w-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center">
                    <span class="text-7xl font-extrabold text-white/30 tracking-wider select-none">{{ strtoupper(substr($project->student->user->name ?? '', 0, 2)) }}</span>
                </div>
                @endif

                <div class="p-8 sm:p-10">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Tervalidasi
                        </span>
                        <span class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-600/20">{{ ucfirst($project->development_model) }}</span>
                        @if($project->validation)
                        @php $avgScore = number_format(($project->validation->functionality_score + $project->validation->code_quality_score + $project->validation->documentation_score + $project->validation->originality_score) / 4, 1); @endphp
                        <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-600/20">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ $avgScore }}
                        </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">{{ $project->title }}</h1>

                    <!-- Author -->
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-slate-100">
                        <div class="h-11 w-11 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg">
                            {{ substr($project->student->user->name ?? '?', 0, 1) }}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 group-hover:text-blue-600 transition">
                                <a href="{{ route('student.profile', $project->student_id) }}" class="hover:underline">
                                    {{ $project->student->user->name ?? 'Siswa' }}
                                </a>
                            </p>
                            <p class="text-sm text-slate-500">{{ $project->student->active_class }} &bull; Angkatan {{ $project->student->year ?? '' }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="prose prose-slate max-w-none mb-8">
                        <h2 class="text-lg font-bold text-slate-900 mb-2">Deskripsi Proyek</h2>
                        <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ $project->description }}</p>
                    </div>

                    <!-- GitHub Link -->
                    @if($project->github_url)
                    <div class="mb-8">
                        <a href="{{ $project->github_url }}" target="_blank" class="inline-flex items-center space-x-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 px-5 py-2.5 rounded-xl hover:bg-slate-50 transition shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                            <span>Lihat Repositori GitHub</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                    @endif

                    <!-- Tech Stack Tags -->
                    @if($project->tech_stack && count($project->tech_stack) > 0)
                    <div class="mb-8">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Tech Stack</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($project->tech_stack as $tech)
                            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $tech }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- GitHub Metadata -->
            @if($project->githubMetadata)
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6 sm:p-8 mb-8">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-slate-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    Statistik GitHub
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->githubMetadata->commit_count ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Total Commit</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->githubMetadata->language ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Bahasa Utama</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->githubMetadata->stars ?? 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Stars</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->githubMetadata->forks ?? 0 }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Forks</div>
                    </div>
                </div>
                @if($project->githubMetadata->last_commit_at)
                <p class="text-xs text-slate-500 mt-3">Commit terakhir: {{ \Carbon\Carbon::parse($project->githubMetadata->last_commit_at)->diffForHumans() }} — <span class="text-slate-700 italic">"{{ Str::limit($project->githubMetadata->last_commit_message, 80) }}"</span></p>
                @endif
            </div>
            @endif

            <!-- Penilaian Guru -->
            @if($project->validation)
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6 sm:p-8 mb-8">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Rincian Penilaian</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->validation->functionality_score ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Fungsionalitas</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->validation->code_quality_score ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Kualitas Kode</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->validation->documentation_score ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Dokumentasi</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                        <div class="text-2xl font-bold text-slate-900">{{ $project->validation->originality_score ?? '-' }}</div>
                        <div class="text-xs text-slate-500 font-medium mt-1">Orisinalitas</div>
                    </div>
                </div>
                @if($project->validation->notes)
                <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-100 text-emerald-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider mb-1 opacity-70">Feedback Guru</h4>
                    <p class="text-sm">{{ $project->validation->notes }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Dokumen Pendukung -->
            @if($project->documentation->where('is_public', true)->count() > 0)
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6 sm:p-8 mb-8">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Dokumen Pendukung
                </h2>
                <div class="space-y-2">
                    @foreach($project->documentation->where('is_public', true) as $doc)
                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3.5 hover:bg-slate-50 transition group">
                        <div class="flex items-center gap-3 min-w-0">
                            @php
                                $docColors = ['pdf' => 'text-rose-500', 'video' => 'text-violet-500', 'image' => 'text-blue-500', 'presentation' => 'text-amber-500', 'spreadsheet' => 'text-emerald-500', 'other' => 'text-slate-500'];
                            @endphp
                            <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $docColors[$doc->doc_type] ?? 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $doc->file_name }}</p>
                                <p class="text-xs text-slate-500">{{ ucfirst($doc->doc_type) }} &bull; {{ number_format($doc->file_size / 1024, 0) }} KB{{ $doc->description ? ' — ' . $doc->description : '' }}</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Link Proyek -->
            @if($project->urls->where('is_public', true)->count() > 0)
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 p-6 sm:p-8 mb-8">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Link Proyek</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach($project->urls->where('is_public', true) as $url)
                    <a href="{{ $url->url }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 border border-slate-200 hover:bg-white hover:border-blue-200 transition shadow-sm">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        {{ $url->description ?: ucwords(str_replace('_', ' ', $url->url_type ?? 'Link')) }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </section>
</x-layouts.public>
