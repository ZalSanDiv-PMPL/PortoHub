<?php

use Livewire\Volt\Component;
use App\Models\Project;

new class extends Component {
    public string $search = '';
    public string $selectedModel = '';
    public int $limit = 0;
    public bool $isLandingPage = false;

    public function with()
    {
        $query = Project::query()
            ->with(['student.user', 'student.classAssignments', 'githubMetadata', 'validation', 'urls'])
            ->where('status', 'approved')
            ->publiclyVisible();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('student.user', function ($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->selectedModel)) {
            $query->where('development_model', $this->selectedModel);
        }

        $query->orderByDesc('approval_date');
        
        if ($this->limit > 0) {
            $query->take($this->limit);
        }

        $projects = $query->get();

        return [
            'projects' => $projects,
        ];
    }

    public function setModelFilter($model)
    {
        $this->selectedModel = $model;
    }
}; ?>

<section id="gallery" class="relative {{ $isLandingPage ? 'py-24' : 'pt-10 pb-24' }} overflow-hidden bg-slate-50 min-h-[calc(100vh-80px)]">
    <!-- Decorative Background -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-blue-100/50 blur-3xl opacity-50"></div>
        <div class="absolute top-40 -left-20 w-72 h-72 rounded-full bg-indigo-100/50 blur-3xl opacity-50"></div>
    </div>

    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Etalase Proyek</h2>
            <p class="mt-4 text-lg leading-8 text-slate-600">Jelajahi karya-karya terbaik dari siswa kami.</p>
        </div>

        <!-- Filters & Search -->
        @if(!$isLandingPage)
        <div class="mb-10 flex flex-col sm:flex-row gap-4 items-center justify-between bg-white/60 backdrop-blur-xl p-4 rounded-2xl border border-white/50 shadow-sm ring-1 ring-slate-200/50">
            <div class="flex items-center space-x-2 overflow-x-auto w-full sm:w-auto pb-2 sm:pb-0 custom-scrollbar">
                <button wire:click="setModelFilter('')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap {{ $selectedModel === '' ? 'bg-blue-600 text-white shadow-md border border-blue-600' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">Semua Kategori</button>
                <button wire:click="setModelFilter('agile')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap {{ $selectedModel === 'agile' ? 'bg-blue-600 text-white shadow-md border border-blue-600' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">Agile / Scrum</button>
                <button wire:click="setModelFilter('waterfall')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap {{ $selectedModel === 'waterfall' ? 'bg-blue-600 text-white shadow-md border border-blue-600' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">Waterfall</button>
                <button wire:click="setModelFilter('other')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap {{ $selectedModel === 'other' ? 'bg-blue-600 text-white shadow-md border border-blue-600' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' }}">Lainnya</button>
            </div>
            
            <div class="relative w-full sm:w-72 flex-shrink-0">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full rounded-xl border-slate-200 bg-white/70 py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm transition hover:bg-white/90" placeholder="Cari proyek atau nama siswa...">
            </div>
        </div>
        @endif

        <!-- Project Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($projects as $project)
                <div class="group relative flex flex-col bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 ring-1 ring-slate-200 hover:ring-blue-200 hover:-translate-y-1">
                    <!-- Project Thumbnail -->
                    <div class="aspect-video w-full bg-gradient-to-br from-slate-100 to-slate-200 relative overflow-hidden rounded-t-2xl transform-gpu">
                        @if($project->thumbnail_path)
                            <img src="{{ asset('storage/' . $project->thumbnail_path) }}" alt="{{ $project->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        @else
                            <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-indigo-500 to-blue-600 opacity-80 transition-transform duration-500 group-hover:scale-105 group-hover:opacity-100">
                                <span class="text-5xl font-extrabold text-white/40 tracking-wider select-none">{{ strtoupper(substr($project->student->user->name, 0, 2)) }}</span>
                            </div>
                        @endif
                        
                        <!-- Badges -->
                        <div class="absolute top-4 right-4 flex space-x-2">
                            @if($project->validation)
                            <div class="bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-lg text-xs font-bold text-emerald-700 shadow-sm flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                {{ number_format(($project->validation->functionality_score + $project->validation->code_quality_score + $project->validation->documentation_score + $project->validation->originality_score) / 4, 1) }}
                            </div>
                            @endif
                        </div>
                        <div class="absolute bottom-4 left-4">
                            <span class="inline-flex items-center rounded-md bg-white/90 backdrop-blur-md px-2 py-1 text-xs font-medium text-slate-700 shadow-sm">
                                {{ ucfirst($project->development_model) }}
                            </span>
                        </div>
                    </div>

                    <!-- Project Info -->
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm overflow-hidden">
                                <x-avatar :url="$project->student->user->avatar_url" :name="$project->student->user->name" />
                            </div>
                            <div class="text-sm">
                                <p class="font-medium text-slate-900">{{ $project->student->user->name }}</p>
                                <p class="text-slate-500 text-xs">{{ $project->student->active_class }}</p>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-slate-900 mb-2 line-clamp-1">{{ $project->title }}</h3>
                        <p class="text-sm text-slate-600 line-clamp-3 mb-4 flex-1">{{ $project->description }}</p>

                        @if($project->tech_stack && count($project->tech_stack) > 0)
                        <div class="flex flex-wrap gap-1.5 mb-4">
                            @foreach(array_slice($project->tech_stack, 0, 5) as $tech)
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $tech }}</span>
                            @endforeach
                            @if(count($project->tech_stack) > 5)
                            <span class="text-[10px] text-slate-400 font-medium self-center">+{{ count($project->tech_stack) - 5 }}</span>
                            @endif
                        </div>
                        @endif

                        <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between">
                            @if($project->github_url)
                            <a href="{{ $project->github_url }}" target="_blank" class="inline-flex items-center text-sm font-semibold text-slate-700 hover:text-blue-600 transition group/link">
                                <svg class="h-5 w-5 mr-1.5 text-slate-400 group-hover/link:text-blue-600 transition" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                Repository
                            </a>
                            @else
                            <span class="text-sm text-slate-400">Tidak ada tautan</span>
                            @endif
                            
                            <a href="{{ route('project.show', $project) }}" class="text-sm font-bold text-blue-600 opacity-0 transform translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                Lihat Detail &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white/50 backdrop-blur-md rounded-3xl border border-dashed border-slate-300">
                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">Tidak ada proyek ditemukan</h3>
                    <p class="mt-1 text-sm text-slate-500">Coba gunakan kata kunci pencarian atau filter yang berbeda.</p>
                </div>
            @endforelse
        </div>
        
        @if($isLandingPage)
        <div class="mt-12 text-center">
            <a href="{{ route('gallery') }}" class="inline-flex items-center space-x-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <span>Lihat Galeri Selengkapnya</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>
