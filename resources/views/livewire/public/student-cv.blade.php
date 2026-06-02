<div>
    <!-- Auto-print script -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); // Give fonts a moment to render
        }
    </script>

    <!-- Header Section -->
    <header class="border-b border-slate-300 pb-4 mb-4">
        <div class="flex flex-col items-center justify-between text-center md:flex-row md:text-left">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight uppercase">{{ $student->user->name }}</h1>
                <p class="text-sm font-semibold text-blue-700 mt-0.5">
                    {{ $student->user->headline ?? 'Software Developer' }}
                </p>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600 font-medium">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $student->user->email }}
                    </span>
                    @if($student->user->githubToken && $student->user->githubToken->github_username)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                        github.com/{{ $student->user->githubToken->github_username }}
                    </span>
                    @endif
                    @if($student->user->linkedin_username)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        linkedin.com/in/{{ $student->user->linkedin_username }}
                    </span>
                    @endif
                </div>
            </div>
            <!-- Profil Singkat / Info Sekolah (One line) -->
            <div class="mt-4 md:mt-0 text-xs text-slate-600 text-right">
                <p class="font-bold text-slate-800 uppercase tracking-widest text-[10px] mb-1">PortoHub Student</p>
                <div class="flex items-center gap-2 justify-end divide-x divide-slate-300">
                    <span class="pr-2">{{ $student->active_class }}</span>
                    <span class="px-2">Angkatan {{ $student->year }}</span>
                    @if($student->nis)
                    <span class="pl-2">NIS {{ $student->nis }}</span>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Professional Summary / Tentang Saya -->
    <section class="mb-6">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-300 pb-1 mb-2 uppercase tracking-widest">Ringkasan Profesional</h2>
        <p class="text-xs text-slate-700 leading-relaxed text-justify">
            Seorang siswa dengan minat tinggi di bidang pengembangan perangkat lunak, khususnya dalam pemrograman web dan aplikasi. 
            Melalui portofolio ini, saya telah membuktikan kemampuan saya dengan menyelesaikan <strong>{{ $stats['total_projects'] }} proyek sukses</strong> 
            yang telah tervalidasi dengan rata-rata skor <strong>{{ $stats['avg_score'] }}/100</strong>. 
            Saya memiliki pengalaman praktis dalam menggunakan teknologi modern dengan total <strong>{{ number_format($stats['total_commits']) }} kontribusi (commits)</strong> di GitHub.
        </p>
    </section>

    <!-- Dua Kolom: Kiri (Keahlian) & Kanan (Proyek) -->
    <div class="grid grid-cols-12 gap-6">
        
        <!-- Kolom Kiri: Keahlian & Pendidikan -->
        <div class="col-span-4 space-y-6">
            <!-- Pendidikan -->
            <section>
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-300 pb-1 mb-2 uppercase tracking-widest">Pendidikan</h2>
                <div>
                    <h3 class="font-bold text-slate-800 text-xs">Sekolah Menengah Kejuruan</h3>
                    <p class="text-[11px] text-slate-600">Rekayasa Perangkat Lunak</p>
                    <p class="text-[10px] text-slate-500 italic">2023 - {{ date('Y') + 1 }} (Perkiraan)</p>
                </div>
            </section>

            <!-- Keahlian -->
            <section>
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-300 pb-1 mb-2 uppercase tracking-widest">Keahlian Utama</h2>
                @if(count($topSkills) > 0)
                    <div class="flex flex-col gap-1.5">
                        @foreach($topSkills as $skill => $count)
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-700">{{ $skill }}</span>
                                <span class="text-[10px] text-slate-500 border border-slate-200 px-1 py-0.5 rounded">{{ $count }} Proyek</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500">Belum ada data teknologi.</p>
                @endif
            </section>
        </div>

        <!-- Kolom Kanan: Pengalaman Proyek -->
        <div class="col-span-8">
            <section>
                <h2 class="text-sm font-bold text-slate-900 border-b border-slate-300 pb-1 mb-3 uppercase tracking-widest">Portofolio & Pengalaman Proyek</h2>
                
                @if($projects->count() > 0)
                    <div class="space-y-4">
                        @foreach($projects as $project)
                            <div class="break-inside-avoid">
                                <div class="flex items-start justify-between mb-0.5">
                                    <h3 class="font-bold text-slate-900 text-xs uppercase">{{ $project->title }}</h3>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded shrink-0">
                                        Skor: {{ $project->validation ? (($project->validation->functionality_score + $project->validation->code_quality_score + $project->validation->documentation_score + $project->validation->originality_score) / 4) : '-' }}
                                    </span>
                                </div>
                                
                                <p class="text-[10px] text-slate-500 italic mb-1.5">
                                    Disetujui (Approved) &bull; 
                                    @if($project->githubMetadata)
                                        Commits: {{ $project->githubMetadata->commit_count ?? 0 }}
                                    @endif
                                </p>

                                <p class="text-[11px] text-slate-700 leading-relaxed text-justify mb-1.5">
                                    {{ $project->description }}
                                </p>

                                @if($project->tech_stack && is_array($project->tech_stack))
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <span class="text-[10px] font-semibold text-slate-600 mr-1">Teknologi:</span>
                                        @foreach($project->tech_stack as $tech)
                                            <span class="text-[9px] uppercase tracking-wider font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                {{ $tech }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic">Belum ada portofolio proyek.</p>
                @endif
            </section>
        </div>
    </div>

    <footer class="mt-12 pt-4 border-t border-slate-200 text-center text-xs text-slate-400 no-print">
        <p>CV ini di-generate otomatis oleh PortoHub pada {{ date('d F Y') }}.</p>
    </footer>
</div>
