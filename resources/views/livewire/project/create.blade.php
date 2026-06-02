<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public string $development_model = 'waterfall';
    public string $github_url = '';
    public string $visibility = 'public';
    public $thumbnail;

    public bool $isLoadingRepos = false;
    public array $githubRepos = [];

    public function mount()
    {
        if (auth()->user()->githubToken) {
            $this->isLoadingRepos = true;
            // Optionally, we could fetch here, or use wire:init to not block page load.
        }
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
                // Silently fail or log
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
        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:15',
            'development_model' => 'required|in:waterfall,agile,other',
            'visibility' => 'required|in:public,restricted,private',
            'github_url' => 'required|url',
            'thumbnail' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'thumbnail.required' => 'Gambar sampul proyek wajib diunggah.',
            'thumbnail.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
            'thumbnail.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'thumbnail.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',
        ]);

        $student = auth()->user()->student;

        if (!$student) {
            session()->flash('error', 'Profil siswa tidak ditemukan.');
            return;
        }

        if (!$student->is_validated) {
            session()->flash('error', 'Akun Anda sedang menunggu validasi oleh Admin Sekolah. Anda belum dapat mengirimkan proyek.');
            return;
        }

        $thumbnailPath = null;
        if ($this->thumbnail) {
            $thumbnailPath = $this->thumbnail->store('thumbnails/' . $student->id, 'public');
        }

        $project = Project::create([
            'student_id' => $student->id,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_path' => $thumbnailPath,
            'development_model' => $this->development_model,
            'github_url' => $this->github_url,
            'visibility' => $this->visibility,
            'status' => 'submitted',
            'submission_date' => now(),
        ]);

        $activeTeachers = $student->teachers()->wherePivot('is_active', true)->get();
        foreach ($activeTeachers as $teacher) {
            $teacher->user->notify(new \App\Notifications\ProjectSubmitted($project, $student));
        }

        if ($this->github_url) {
            \Illuminate\Support\Facades\Artisan::call('github:sync-metadata', ['--project' => $project->id]);
        }

        session()->flash('success', 'Proyek berhasil diajukan untuk direview.');
        return redirect()->route('dashboard');
    }
}; ?>

<div>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <a wire:navigate href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-700 mb-2 transition">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali ke Dashboard
                    </a>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Ajukan Proyek Baru</h2>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow-sm ring-1 ring-slate-200 sm:rounded-2xl">
                <form wire:submit.prevent="submitProject">
                    <div class="p-6 sm:p-8 space-y-8">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detail Proyek</h3>
                            <p class="mt-1 text-sm text-slate-500">Isi detail proyek portofolio Anda. Jika Anda menghubungkan GitHub, pilih langsung dari repo Anda.</p>
                        </div>

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
                            <style>
                                .custom-scrollbar::-webkit-scrollbar { height: 6px; }
                                .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
                                .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
                            </style>
                            <div class="relative -mx-1">
                                <div class="overflow-x-auto flex gap-2 snap-x snap-mandatory scroll-smooth custom-scrollbar pb-2" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                                    @foreach($githubRepos as $repo)
                                    @if($repo['is_submitted'])
                                    <div class="snap-start flex-none p-1">
                                        <div class="relative w-64 p-3 rounded-xl border-2 border-slate-200 bg-slate-50 opacity-70 cursor-not-allowed">
                                            <div class="absolute top-2 right-2 flex items-center bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full ring-1 ring-emerald-600/20">
                                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Diajukan
                                            </div>
                                            <div class="flex items-center space-x-2 text-slate-500 font-semibold text-sm">
                                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                                </svg>
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
                                                <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                                </svg>
                                                <span class="truncate block">{{ $repo['name'] }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $repo['description'] ?? 'Tidak ada deskripsi' }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            @else
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                                <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-700">Tidak ada repositori publik ditemukan, atau token kadaluarsa.</p>
                                <p class="text-xs text-slate-500 mt-1">Anda bisa memasukkan URL GitHub secara manual di bawah, atau periksa halaman Profil untuk sinkronisasi ulang.</p>
                            </div>
                            @endif
                        </div>
                        @endif

                        <div class="space-y-6">
                            <div>
                                <label for="thumbnail" class="block text-sm font-semibold text-slate-900 mb-1">Gambar Sampul Proyek <span class="text-red-500">*</span></label>
                                <input type="file" wire:model="thumbnail" id="thumbnail" accept=".jpg,.jpeg,.png,.webp"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-xl bg-white focus:outline-none">
                                <p class="mt-1 text-xs text-slate-500">Format JPG, PNG, WebP — maksimal 2MB.</p>

                                <div wire:loading wire:target="thumbnail" class="mt-2 inline-flex items-center gap-1.5 text-blue-600">
                                    <svg class="animate-spin h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs font-medium leading-none">Mengunggah gambar...</span>
                                </div>

                                @error('thumbnail') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror

                                @if ($thumbnail)
                                <div class="mt-2 relative inline-block">
                                    <img src="{{ $thumbnail->temporaryUrl() }}" alt="Preview" class="h-32 object-cover rounded-xl border border-slate-200 shadow-sm">
                                </div>
                                @endif
                            </div>

                            <div>
                                <label for="title" class="block text-sm font-semibold text-slate-900 mb-1">Judul Proyek <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="title" id="title" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Misal: Sistem Manajemen Keuangan">
                                @error('title') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-semibold text-slate-900 mb-1">Deskripsi Singkat <span class="text-red-500">*</span></label>
                                <textarea wire:model="description" id="description" rows="4" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Jelaskan secara singkat apa proyek ini dan fitur utamanya..."></textarea>
                                @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                    <label for="github_url" class="block text-sm font-semibold text-slate-900 mb-1">URL GitHub <span class="text-red-500">*</span></label>
                                    <input type="url" wire:model="github_url" id="github_url" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="https://github.com/username/repo">
                                    @error('github_url') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="visibility" class="block text-sm font-semibold text-slate-900 mb-1">Visibilitas Proyek</label>
                                <select wire:model="visibility" id="visibility" class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="public">Publik — Dapat dilihat semua orang</option>
                                    <option value="restricted">Terbatas — Hanya guru & industri terundang</option>
                                    <option value="private">Privat — Hanya saya & guru pengampu</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Atur siapa yang dapat melihat proyek Anda di galeri publik.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 p-6 sm:px-8 border-t border-slate-200 flex flex-col sm:flex-row-reverse gap-3 rounded-b-2xl">
                        <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:w-auto transition items-center">
                            <span wire:loading.remove wire:target="submitProject">Kirim Proyek</span>
                            <span wire:loading.inline-flex wire:target="submitProject" class="items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                        <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex w-full justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto transition text-center items-center">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
