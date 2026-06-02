<div>
    <x-slot:meta>
        <meta property="og:title" content="{{ $student->user->name }} - Profil Portofolio">
        <meta property="og:description" content="{{ $student->user->headline ?? 'Lihat portofolio lengkap saya di PortoHub!' }}">
        <meta property="og:url" content="{{ url('/@' . $student->user->username) }}">
        <meta property="og:type" content="profile">
        @if($projects->count() > 0 && $projects->first()->thumbnail_path)
        <meta property="og:image" content="{{ asset('storage/' . $projects->first()->thumbnail_path) }}">
        @endif
    </x-slot:meta>
    <!-- Profile Header Banner -->
    <div class="relative bg-gradient-to-r from-blue-600 to-violet-600 pb-32 pt-20 shadow-inner">
        <div class="absolute inset-0 overflow-hidden">
            <svg class="absolute left-0 top-0 h-full w-full opacity-20" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0 100 C 20 0 50 0 100 100 Z" fill="currentColor"></path>
            </svg>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('gallery') }}" class="inline-flex items-center text-white/80 hover:text-white text-sm font-medium transition mb-8" wire:navigate>
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Galeri
            </a>
            
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex items-center gap-6">
                    <!-- Avatar -->
                    <div class="h-24 w-24 rounded-full bg-white/20 p-1 flex-shrink-0 shadow-lg ring-4 ring-white/10">
                        <div class="h-full w-full rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-3xl overflow-hidden">
                            <x-avatar :url="$student->user->avatar_url" :name="$student->user->name" />
                        </div>
                    </div>
                    <!-- Identity -->
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $student->user->name }}</h1>
                        @if($student->user->headline)
                        <p class="mt-2 text-lg text-blue-50 font-medium leading-tight max-w-xl">{{ $student->user->headline }}</p>
                        @endif
                        <p class="mt-2 text-sm font-medium text-blue-200 flex items-center gap-2">
                            <svg class="w-4 h-4 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Siswa &bull; {{ $student->active_class }} &bull; Angkatan {{ $student->year }}
                        </p>
                        @php
                            $ghUsername = $student->user->githubToken->github_username ?? null;
                            $liUsername = $student->user->linkedin_username ?? null;
                        @endphp
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            @if($ghUsername)
                            <a href="https://github.com/{{ $ghUsername }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1 rounded-full transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                                {{ $ghUsername }}
                            </a>
                            @endif
                            
                            @if($liUsername)
                            <a href="https://linkedin.com/in/{{ $liUsername }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1 rounded-full transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                {{ $liUsername }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-6 flex flex-col-reverse justify-stretch space-y-4 space-y-reverse sm:flex-row-reverse sm:justify-end sm:space-x-3 sm:space-y-0 sm:space-x-reverse md:mt-0 md:flex-row md:space-x-3">
                    @if(auth()->check() && auth()->id() === $student->user_id)
                        <a href="{{ route('profile') }}" class="inline-flex items-center justify-center rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-white/20 ring-1 ring-inset ring-white/20 transition" wire:navigate>
                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            Edit Profil
                        </a>
                        <a target="_blank" href="{{ route('student.cv', $student->user->username) }}" class="inline-flex items-center justify-center rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-white/20 ring-1 ring-inset ring-white/20 transition hidden sm:inline-flex">
                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download CV
                        </a>
                    @endif
                    <div x-data="{
                        shareProfile() {
                            const url = '{{ url('/@' . $student->user->username) }}';
                            const title = @json($student->user->name . ' - Portofolio Proyek');
                            
                            if (navigator.share) {
                                navigator.share({
                                    title: title,
                                    url: url
                                }).catch(console.error);
                            } else {
                                navigator.clipboard.writeText(url);
                                alert('Link profil berhasil disalin ke clipboard!');
                            }
                        }
                    }">
                        <button type="button" @click="shareProfile" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition w-full sm:w-auto">
                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            Bagikan Profil
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="-mt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Sidebar (Stats & Skills) -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Aggregate Stats -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl ring-1 ring-white/50 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-b from-white/60 to-transparent"></div>
                    <div class="p-6 relative z-10">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest mb-5 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Statistik
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center flex flex-col items-center justify-center relative overflow-hidden group">
                                <div class="absolute -right-2 -top-2 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <svg class="w-16 h-16 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M19.333 3h-14.666c-1.473 0-2.667 1.194-2.667 2.667v12.666c0 1.473 1.194 2.667 2.667 2.667h14.666c1.473 0 2.667-1.194 2.667-2.667v-12.666c0-1.473-1.194-2.667-2.667-2.667zm-14.666 2h14.666c.368 0 .667.299.667.667v8.333h-16v-8.333c0-.368.299-.667.667-.667zm14.666 14h-14.666c-.368 0-.667-.299-.667-.667v-3.333h16v3.333c0 .368-.299.667-.667.667z"/></svg>
                                </div>
                                <p class="text-3xl font-black text-slate-900 drop-shadow-sm">{{ $stats['total_projects'] }}</p>
                                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase tracking-widest">Proyek</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center flex flex-col items-center justify-center relative overflow-hidden group">
                                <div class="absolute -right-2 -top-2 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <svg class="w-16 h-16 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg>
                                </div>
                                <p class="text-3xl font-black text-yellow-600 drop-shadow-sm">{{ $stats['avg_score'] }}</p>
                                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase tracking-widest">Rata-rata Skor</p>
                            </div>
                            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 text-center col-span-2 relative overflow-hidden group">
                                <div class="absolute -right-4 top-1/2 -translate-y-1/2 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <svg class="w-24 h-24 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M17.842 6c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm-11.684 0c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm11.684 10c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm-11.684 0c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm5.842-5c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm0 5c-2.148 0-4.043-1.171-5.068-2.915l-1.748.971c1.378 2.348 3.931 3.944 6.816 3.944s5.438-1.596 6.816-3.944l-1.748-.971c-1.025 1.744-2.92 2.915-5.068 2.915zm0-10c2.148 0 4.043 1.171 5.068 2.915l1.748-.971c-1.378-2.348-3.931-3.944-6.816-3.944s-5.438 1.596-6.816 3.944l1.748.971c1.025-1.744 2.92-2.915 5.068-2.915z"/></svg>
                                </div>
                                <p class="text-3xl font-black text-slate-900 drop-shadow-sm">{{ number_format($stats['total_commits']) }}</p>
                                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase tracking-widest">Total Kontribusi GitHub (Commits)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Skills -->
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            Keahlian Utama
                        </h3>
                        @if(count($topSkills) > 0)
                            <div class="flex flex-wrap gap-2.5">
                                @foreach($topSkills as $skill => $count)
                                    <span class="inline-flex items-center rounded-xl bg-gradient-to-r from-violet-50 to-indigo-50 px-3 py-2 text-sm font-bold text-slate-700 shadow-sm border border-slate-200/60 hover:shadow-md hover:border-violet-300 transition-all cursor-default">
                                        {{ $skill }} 
                                        <span class="ml-2 rounded-full bg-violet-600 text-white px-2 py-0.5 text-[10px] font-black shadow-inner">
                                            {{ $count }}
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <p class="text-sm text-slate-500">Belum ada data teknologi.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column (Projects) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4">Etalase Proyek ({{ $projects->count() }})</h2>
                    
                    @if($projects->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($projects as $project)
                                <div class="group flex flex-col bg-white rounded-3xl border border-slate-100 shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
                                    <div class="aspect-[16/9] relative bg-slate-100 overflow-hidden">
                                        @if($project->thumbnail_path)
                                            <img src="{{ asset('storage/' . $project->thumbnail_path) }}" alt="{{ $project->title }}" loading="lazy" class="object-cover w-full h-full group-hover:scale-110 transition-transform duration-500 ease-out">
                                        @else
                                            <div class="flex h-full items-center justify-center text-slate-400 bg-gradient-to-br from-slate-50 to-slate-200">
                                                <svg class="h-10 w-10 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent opacity-60 group-hover:opacity-80 transition-opacity duration-300"></div>
                                        
                                        <!-- Verified Badge -->
                                        @if($project->status === 'approved')
                                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur shadow-lg px-2.5 py-1.5 rounded-full flex items-center gap-1.5 transform translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                            <div class="bg-blue-500 rounded-full p-0.5 text-white">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-700 tracking-wide">VERIFIED</span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="p-5 flex flex-col flex-grow">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <h3 class="font-bold text-slate-900 text-lg leading-tight group-hover:text-blue-600 transition-colors line-clamp-1"><a href="{{ route('project.show', $project->id) }}" class="focus:outline-none" wire:navigate><span class="absolute inset-0" aria-hidden="true"></span>{{ $project->title }}</a></h3>
                                        </div>
                                        
                                        <p class="mt-1 text-sm text-slate-500 line-clamp-2 mb-4">{{ $project->description }}</p>
                                        
                                        <div class="mt-auto space-y-4">
                                            @if($project->tech_stack)
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach(array_slice($project->tech_stack, 0, 3) as $tech)
                                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ $tech }}</span>
                                                    @endforeach
                                                    @if(count($project->tech_stack) > 3)
                                                        <span class="inline-flex items-center rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">+{{ count($project->tech_stack) - 3 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                                                @if($project->validation)
                                                <div class="flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-md">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Skor: {{ ($project->validation->functionality_score + $project->validation->code_quality_score + $project->validation->documentation_score + $project->validation->originality_score) / 4 }}
                                                </div>
                                                @endif
                                                <span class="text-xs text-blue-600 font-semibold group-hover:underline">Lihat Detail &rarr;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <h3 class="mt-2 text-sm font-semibold text-slate-900">Belum ada proyek</h3>
                            <p class="mt-1 text-sm text-slate-500">Siswa ini belum memiliki proyek publik yang disetujui.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
