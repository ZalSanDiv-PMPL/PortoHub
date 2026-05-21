<?php

use Livewire\Volt\Component;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;

new class extends Component {
    #[Validate('required|min:5')]
    public string $title = '';

    #[Validate('required|min:15')]
    public string $description = '';

    #[Validate('required|in:waterfall,agile,other')]
    public string $development_model = 'waterfall';

    #[Validate('nullable|url')]
    public string $github_url = '';

    public bool $isModalOpen = false;
    public bool $isLoadingRepos = false;
    public array $githubRepos = [];

    public function openModal()
    {
        $this->isModalOpen = true;
        if (auth()->user()->githubToken) {
            $this->isLoadingRepos = true;
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->isLoadingRepos = false;
        $this->reset(['title', 'description', 'development_model', 'github_url', 'githubRepos']);
        $this->resetValidation();
    }

    public function fetchGithubRepos()
    {
        $token = auth()->user()->githubToken;
        if ($token && $token->access_token) {
            try {
                $response = Http::withToken($token->access_token)
                    ->get('https://api.github.com/user/repos', [
                        'sort' => 'updated',
                        'per_page' => 15
                    ]);
                
                if ($response->successful()) {
                    $student = auth()->user()->student;
                    $existingUrls = $student ? $student->projects()->pluck('github_url')->filter()->toArray() : [];

                    $this->githubRepos = collect($response->json())->map(function($repo) use ($existingUrls) {
                        return [
                            'name' => $repo['name'],
                            'url' => $repo['html_url'],
                            'description' => $repo['description'],
                            'is_submitted' => in_array($repo['html_url'], $existingUrls),
                        ];
                    })->toArray();
                }
            } catch (\Exception $e) {
                // Ignore failure
            }
        }
        $this->isLoadingRepos = false;
    }
    
    public function selectRepo($url, $name, $description)
    {
        $this->github_url = $url;
        $this->title = str_replace('-', ' ', Str::title($name));
        $this->description = $description ?? '';
    }

    public function submitProject()
    {
        $this->validate();

        $student = auth()->user()->student;

        if (!$student) {
            session()->flash('error', 'Profil siswa tidak ditemukan.');
            return;
        }

        Project::create([
            'student_id' => $student->id,
            'title' => $this->title,
            'description' => $this->description,
            'development_model' => $this->development_model,
            'github_url' => $this->github_url,
            'status' => 'submitted',
            'submission_date' => now(),
        ]);

        $this->closeModal();
        session()->flash('success', 'Proyek berhasil diajukan untuk direviu.');
    }

    public function with()
    {
        $student = auth()->user()->student;
        $projects = $student ? $student->projects()->orderBy('created_at', 'desc')->get() : collect();

        $totalProyek = $projects->count();
        $sedangDireviu = $projects->where('status', 'under_review')->count();
        $proyekLulus = $projects->where('status', 'approved')->count();

        return [
            'totalProyek' => $totalProyek,
            'sedangDireviu' => $sedangDireviu,
            'proyekLulus' => $proyekLulus,
            'projects' => $projects,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Halo, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p class="mt-2 text-slate-600">Selamat datang di Dashboard Siswa. Pantau terus progres proyekmu di sini.</p>
        </div>
        <button wire:click="openModal" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 group">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 transition-transform group-hover:scale-110" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Ajukan Proyek Baru
        </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <!-- Card 1 -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-blue-50 p-3">
                    <svg class="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Proyek</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalProyek }}</p>
            </dd>
        </div>

        <!-- Card 2 -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-amber-50 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Sedang Direviu</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $sedangDireviu }}</p>
            </dd>
        </div>

        <!-- Card 3 -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-emerald-50 p-3">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Proyek Lulus</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $proyekLulus }}</p>
            </dd>
        </div>
    </div>

    <!-- Proyek Aktif Section -->
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900">Proyek Saya</h3>
        <div class="flex space-x-2">
            <!-- Optional Filters / Search can go here -->
        </div>
    </div>

    @if($projects->isEmpty())
    <!-- Empty State -->
    <div class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
        </svg>
        <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada proyek</h3>
        <p class="mt-1 text-sm text-slate-500">Mulai bangun portofolio Anda dengan mengajukan proyek pertama.</p>
        <div class="mt-6">
            <button type="button" wire:click="openModal" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajukan Proyek
            </button>
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($projects as $project)
            <div class="relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                <div class="p-6 flex-1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                            @if($project->status === 'approved') bg-emerald-50 text-emerald-700 ring-emerald-600/20
                            @elseif($project->status === 'under_review') bg-amber-50 text-amber-700 ring-amber-600/20
                            @elseif($project->status === 'submitted') bg-blue-50 text-blue-700 ring-blue-600/20
                            @else bg-slate-50 text-slate-700 ring-slate-600/20 @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $project->created_at->format('d M Y') }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $project->title }}</h3>
                    <p class="text-sm text-slate-600 line-clamp-3">{{ $project->description }}</p>
                </div>
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ $project->github_url }}" target="_blank" class="text-sm font-semibold text-blue-600 hover:text-blue-500">Repository</a>
                    <button class="text-sm font-semibold text-slate-700 hover:text-slate-900">Detail &rarr;</button>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Modal Form Pengajuan Proyek -->
    @if($isModalOpen)
    <style>
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleUp { from { opacity: 0; transform: translateY(10px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
        .animate-scale-up { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .custom-scrollbar::-webkit-scrollbar { height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
    </style>
    <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity animate-fade-in"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl ring-1 ring-slate-200 animate-scale-up">
                    
                    <form wire:submit="submitProject">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <!-- Header Modal (Top-down layout) -->
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-title">Ajukan Proyek Baru</h3>
                                    <p class="mt-1 text-sm text-slate-500">Isi detail proyek portofolio Anda. Jika Anda menghubungkan GitHub, pilih langsung dari repo Anda.</p>
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <!-- GitHub Repos (Asynchronous Loading) -->
                                @if(auth()->user()->githubToken)
                                <div wire:init="fetchGithubRepos">
                                    <label class="block text-sm font-semibold text-slate-900 mb-2">Ambil dari Repositori GitHub Anda</label>
                                    
                                    @if($isLoadingRepos)
                                    <!-- Skeleton Loading Fixed Alignment -->
                                    <div class="relative -mx-1">
                                        <div class="flex gap-2 overflow-hidden">
                                            <div class="snap-start flex-none p-1">
                                                <div class="w-64 h-[76px] bg-slate-100 rounded-xl animate-pulse"></div>
                                            </div>
                                            <div class="snap-start flex-none p-1 hidden sm:block">
                                                <div class="w-64 h-[76px] bg-slate-100 rounded-xl animate-pulse"></div>
                                            </div>
                                            <div class="snap-start flex-none p-1 hidden md:block">
                                                <div class="w-64 h-[76px] bg-slate-100 rounded-xl animate-pulse"></div>
                                            </div>
                                        </div>
                                    </div>
                                    @elseif(!empty($githubRepos))
                                    <div class="relative -mx-1">
                                        <div class="overflow-x-auto flex gap-2 snap-x snap-mandatory scroll-smooth custom-scrollbar" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                                            @foreach($githubRepos as $repo)
                                            @if($repo['is_submitted'])
                                            <div class="snap-start flex-none p-1">
                                                <div class="relative w-64 p-3 rounded-xl border-2 border-slate-200 bg-slate-50 opacity-70 cursor-not-allowed">
                                                    <div class="absolute top-2 right-2 flex items-center bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20">
                                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        Diajukan
                                                    </div>
                                                    <div class="flex items-center space-x-2 text-slate-500 font-semibold text-sm">
                                                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                                        <span class="truncate block">{{ $repo['name'] }}</span>
                                                    </div>
                                                    <p class="text-xs text-slate-400 mt-1 line-clamp-1">{{ $repo['description'] ?? 'Tidak ada deskripsi' }}</p>
                                                </div>
                                            </div>
                                            @else
                                            <div class="snap-start flex-none p-1">
                                                <div wire:click="selectRepo('{{ $repo['url'] }}', '{{ addslashes($repo['name']) }}', '{{ addslashes($repo['description'] ?? '') }}')" 
                                                     class="relative w-64 p-3 rounded-xl border-2 transition cursor-pointer @if($github_url === $repo['url']) border-blue-500 bg-blue-50 ring-2 ring-blue-500 ring-offset-1 @else border-slate-200 hover:border-blue-400 hover:bg-slate-50 @endif">
                                                    <div class="flex items-center space-x-2 text-slate-900 font-semibold text-sm">
                                                        <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                                        <span class="truncate block">{{ $repo['name'] }}</span>
                                                    </div>
                                                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $repo['description'] ?? 'Tidak ada deskripsi' }}</p>
                                                </div>
                                            </div>
                                            @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <div>
                                    <label for="title" class="block text-sm font-semibold text-slate-900 mb-1">Judul Proyek</label>
                                    <input type="text" wire:model="title" id="title" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Misal: Sistem Manajemen Keuangan">
                                    @error('title') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-semibold text-slate-900 mb-1">Deskripsi Singkat</label>
                                    <textarea wire:model="description" id="description" rows="3" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Jelaskan secara singkat apa proyek ini dan fitur utamanya..."></textarea>
                                    @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="development_model" class="block text-sm font-semibold text-slate-900 mb-1">Model Pengembangan</label>
                                        <select wire:model="development_model" id="development_model" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="waterfall">Waterfall</option>
                                            <option value="agile">Agile / Scrum</option>
                                            <option value="other">Lainnya</option>
                                        </select>
                                        @error('development_model') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="github_url" class="block text-sm font-semibold text-slate-900 mb-1">URL GitHub (Opsional)</label>
                                        <input type="url" wire:model="github_url" id="github_url" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="https://github.com/username/repo">
                                        @error('github_url') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:ml-3 sm:w-auto transition">
                                <span wire:loading.remove wire:target="submitProject">Kirim Proyek</span>
                                <span wire:loading wire:target="submitProject">Menyimpan...</span>
                            </button>
                            <button type="button" wire:click="closeModal" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
