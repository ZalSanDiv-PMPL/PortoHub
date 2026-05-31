<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Comment;
use App\Models\Documentation;
use App\Models\ProjectUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads {
        _finishUpload as traitFinishUpload;
    }

    public function _finishUpload($name, $tmpFilenames, $isMultiple)
    {
        // Cegat bug nama file kosong yang menyebabkan UnableToRetrieveMetadata
        if (empty($tmpFilenames) || $tmpFilenames === [''] || (isset($tmpFilenames[0]) && $tmpFilenames[0] === '')) {
            $this->addError($name, 'Gagal mengunggah gambar. Ukuran file mungkin melebihi batasan server (maks. 2MB).');
            return;
        }

        $this->traitFinishUpload($name, $tmpFilenames, $isMultiple);
    }
    #[Validate('required|min:5', message: [
        'title.required' => 'Judul proyek wajib diisi.',
        'title.min' => 'Judul proyek minimal 5 karakter.',
    ])]
    public string $title = '';

    #[Validate('required|min:15', message: [
        'description.required' => 'Deskripsi proyek wajib diisi.',
        'description.min' => 'Deskripsi proyek minimal 15 karakter.',
    ])]
    public string $description = '';

    #[Validate('required|in:waterfall,agile,other', message: [
        'development_model.required' => 'Model pengembangan wajib dipilih.',
        'development_model.in' => 'Model pengembangan tidak valid.',
    ])]
    public string $development_model = 'waterfall';

    #[Validate('nullable|url', message: [
        'github_url.url' => 'URL GitHub harus berupa alamat yang valid.',
    ])]
    public string $github_url = '';

    public string $visibility = 'public';

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:2048', message: [
        'thumbnail.required' => 'Gambar sampul proyek wajib diunggah.',
        'thumbnail.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
        'thumbnail.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
        'thumbnail.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',
    ])]
    public $thumbnail;

    public bool $isModalOpen = false;
    public bool $isLoadingRepos = false;
    public array $githubRepos = [];

    // Modal detail
    public bool $isDetailModalOpen = false;
    public ?Project $selectedProjectDetail = null;
    public array $detailComments = [];

    // Modal dokumentasi
    public bool $isDocModalOpen = false;
    public ?int $docProjectId = null;
    public $docFile;
    public string $docType = 'pdf';
    public string $docDescription = '';
    public bool $docIsPublic = true;
    public array $projectDocs = [];

    // Modal Link
    public bool $isLinkModalOpen = false;
    public ?int $linkProjectId = null;
    public string $linkUrl = '';
    public string $linkType = 'live_demo';
    public string $linkDescription = '';
    public bool $linkIsPublic = true;
    public array $projectLinks = [];

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
        $this->reset(['title', 'description', 'development_model', 'github_url', 'visibility', 'githubRepos', 'thumbnail']);
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

        if ($this->github_url) {
            \Illuminate\Support\Facades\Artisan::call('github:sync-metadata', ['--project' => $project->id]);
        }

        $this->closeModal();
        session()->flash('success', 'Proyek berhasil diajukan untuk direviu.');
    }

    public function with()
    {
        $student = auth()->user()->student;
        $projects = $student ? $student->projects()->with('validation')->orderBy('created_at', 'desc')->get() : collect();

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

    public function openDetailModal($id)
    {
        $this->selectedProjectDetail = Project::with(['validation'])->find($id);
        
        // Load comments for this project
        $this->detailComments = Comment::where('project_id', $id)
            ->with('teacher.user')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        // Mark pending comments as viewed
        Comment::where('project_id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'viewed']);

        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedProjectDetail = null;
        $this->detailComments = [];
    }

    public function resubmitProject()
    {
        if ($this->selectedProjectDetail && $this->selectedProjectDetail->status === 'rejected') {
            $this->selectedProjectDetail->update([
                'status' => 'submitted',
                'submission_date' => now()
            ]);
            $this->closeDetailModal();
            session()->flash('success', 'Proyek berhasil diajukan ulang untuk direviu.');
        }
    }

    // === Dokumentasi Methods ===

    public function openDocModal($projectId)
    {
        $student = auth()->user()->student;
        $project = Project::where('student_id', $student->id)->find($projectId);
        if (!$project) return;

        $this->docProjectId = $projectId;
        $this->loadDocs();
        $this->isDocModalOpen = true;
    }

    public function closeDocModal()
    {
        $this->isDocModalOpen = false;
        $this->docProjectId = null;
        $this->docFile = null;
        $this->docType = 'pdf';
        $this->docDescription = '';
        $this->docIsPublic = true;
        $this->projectDocs = [];
        $this->resetValidation();
    }

    private function loadDocs()
    {
        if ($this->docProjectId) {
            $this->projectDocs = Documentation::where('project_id', $this->docProjectId)
                ->orderByDesc('created_at')
                ->get()
                ->toArray();
        }
    }

    public function uploadDocument()
    {
        $this->validate([
            'docFile' => 'required|file|max:10240|mimes:pdf,mp4,avi,mov,png,jpg,jpeg,webp,xlsx,pptx,docx',
            'docType' => 'required|in:pdf,video,image,spreadsheet,presentation,other',
            'docDescription' => 'nullable|string|max:255',
        ], [
            'docFile.required' => 'File wajib diunggah.',
            'docFile.max' => 'Ukuran file maksimal 10MB.',
            'docFile.mimes' => 'Format file tidak didukung.',
        ]);

        $file = $this->docFile;
        $path = $file->store('documentation/' . $this->docProjectId, 'public');

        Documentation::create([
            'project_id' => $this->docProjectId,
            'doc_type' => $this->docType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $this->docDescription,
            'is_public' => $this->docIsPublic,
        ]);

        $this->docFile = null;
        $this->docDescription = '';
        $this->docType = 'pdf';
        $this->docIsPublic = true;
        $this->loadDocs();
        session()->flash('doc-success', 'Dokumen berhasil diunggah.');
    }

    public function deleteDocument($docId)
    {
        $doc = Documentation::where('project_id', $this->docProjectId)->find($docId);
        if ($doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
            $this->loadDocs();
        }
    }

    // === Link / Tautan Methods ===

    public function openLinkModal($projectId)
    {
        $student = auth()->user()->student;
        $project = Project::where('student_id', $student->id)->find($projectId);
        if (!$project) return;

        $this->linkProjectId = $projectId;
        $this->loadLinks();
        $this->isLinkModalOpen = true;
    }

    public function closeLinkModal()
    {
        $this->isLinkModalOpen = false;
        $this->linkProjectId = null;
        $this->linkUrl = '';
        $this->linkType = 'live_demo';
        $this->linkDescription = '';
        $this->linkIsPublic = true;
        $this->projectLinks = [];
        $this->resetValidation();
    }

    private function loadLinks()
    {
        if ($this->linkProjectId) {
            $this->projectLinks = ProjectUrl::where('project_id', $this->linkProjectId)
                ->orderByDesc('created_at')
                ->get()
                ->toArray();
        }
    }

    public function addLink()
    {
        $this->validate([
            'linkUrl' => 'required|url|max:255',
            'linkType' => 'required|in:live_demo,video_tutorial,documentation,design,other',
            'linkDescription' => 'nullable|string|max:255',
            'linkIsPublic' => 'boolean',
        ]);

        ProjectUrl::create([
            'project_id' => $this->linkProjectId,
            'url_type' => $this->linkType,
            'url' => $this->linkUrl,
            'description' => $this->linkDescription,
            'is_public' => $this->linkIsPublic,
        ]);

        $this->linkUrl = '';
        $this->linkDescription = '';
        $this->loadLinks();
        session()->flash('link-success', 'Link berhasil ditambahkan.');
    }

    public function deleteLink($linkId)
    {
        $link = ProjectUrl::where('project_id', $this->linkProjectId)->find($linkId);
        if ($link) {
            $link->delete();
            $this->loadLinks();
        }
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Halo, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p class="mt-2 text-slate-600">Selamat datang di Dashboard Siswa. Pantau terus progres proyekmu di sini.</p>
        </div>
        
        @if(auth()->user()->student && auth()->user()->student->is_validated)
        <button wire:click="openModal" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 group">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 transition-transform group-hover:scale-110" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Ajukan Proyek Baru
        </button>
        @else
        <button disabled class="inline-flex items-center justify-center rounded-xl bg-slate-300 cursor-not-allowed px-5 py-2.5 text-sm font-semibold text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Ajukan Proyek Baru (Terkunci)
        </button>
        @endif
    </div>

    @if(auth()->user()->student && empty(auth()->user()->student->nis))
    <!-- NIS Warning Banner -->
    <div class="mb-8 p-4 rounded-xl bg-amber-50/80 backdrop-blur-md border border-amber-200 text-amber-800 flex items-start sm:items-center justify-between">
        <div class="flex items-start sm:items-center">
            <svg class="w-6 h-6 mr-3 flex-shrink-0 text-amber-600 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h4 class="font-bold text-sm">Data Akademik Belum Lengkap</h4>
                <p class="text-sm mt-1 sm:mt-0">Admin tidak dapat memvalidasi akun Anda jika NIS kosong. Mohon lengkapi Data Akademik Anda di pengaturan profil.</p>
            </div>
        </div>
        <a href="{{ route('profile') }}" class="ml-4 inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500 whitespace-nowrap">
            Isi NIS Sekarang
        </a>
    </div>
    @elseif(auth()->user()->student && !auth()->user()->student->is_validated)
    <!-- Validation Warning Banner -->
    <div class="mb-8 p-4 rounded-xl bg-blue-50/80 backdrop-blur-md border border-blue-200 text-blue-800 flex items-start sm:items-center">
        <svg class="w-6 h-6 mr-3 flex-shrink-0 text-blue-600 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <h4 class="font-bold text-sm">Akun Anda sedang dalam peninjauan.</h4>
            <p class="text-sm mt-1 sm:mt-0">Data Anda sudah lengkap. Admin sekolah perlu menyetujui akun Anda dan menempatkan Anda ke dalam kelas sebelum Anda bisa mengirimkan proyek portofolio.</p>
        </div>
    </div>
    @endif

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
            @if(auth()->user()->student && auth()->user()->student->is_validated)
            <button type="button" wire:click="openModal" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajukan Proyek
            </button>
            @else
            <button disabled type="button" class="inline-flex items-center rounded-xl bg-slate-300 px-4 py-2 text-sm font-semibold text-white shadow-sm cursor-not-allowed">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajukan Proyek (Terkunci)
            </button>
            @endif
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($projects as $project)
            <div class="relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                <div class="p-6 flex-1">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                @if($project->status === 'approved') bg-emerald-50 text-emerald-700 ring-emerald-600/20
                                @elseif($project->status === 'under_review') bg-amber-50 text-amber-700 ring-amber-600/20
                                @elseif($project->status === 'submitted') bg-blue-50 text-blue-700 ring-blue-600/20
                                @else bg-slate-50 text-slate-700 ring-slate-600/20 @endif
                            ">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                            @if($project->visibility !== 'public')
                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $project->visibility === 'private' ? 'bg-slate-100 text-slate-600' : 'bg-violet-50 text-violet-600' }} ring-1 ring-inset ring-current/20">
                                @if($project->visibility === 'private')
                                <svg class="w-2.5 h-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Privat
                                @else
                                <svg class="w-2.5 h-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Terbatas
                                @endif
                            </span>
                            @endif
                        </div>
                        <span class="text-xs text-slate-500">{{ $project->created_at->format('d M Y') }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $project->title }}</h3>
                    <p class="text-sm text-slate-600 line-clamp-3">{{ $project->description }}</p>
                    @if($project->tech_stack && count($project->tech_stack) > 0)
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        @foreach(array_slice($project->tech_stack, 0, 4) as $tech)
                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $tech }}</span>
                        @endforeach
                        @if(count($project->tech_stack) > 4)
                        <span class="text-[10px] text-slate-400 font-medium self-center">+{{ count($project->tech_stack) - 4 }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ $project->github_url }}" target="_blank" class="text-sm font-semibold text-blue-600 hover:text-blue-500">Repository</a>
                        <button wire:click="openLinkModal({{ $project->id }})" class="text-sm font-semibold text-amber-600 hover:text-amber-500 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Tautan
                        </button>
                        <button wire:click="openDocModal({{ $project->id }})" class="text-sm font-semibold text-violet-600 hover:text-violet-500 inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Dokumen
                        </button>
                    </div>
                    <button wire:click="openDetailModal({{ $project->id }})" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Detail &rarr;</button>
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
                    
                    <form wire:submit.prevent="submitProject">
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
                                    @else
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                                        <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <p class="text-sm font-medium text-slate-700">Tidak ada repositori publik ditemukan, atau token kadaluarsa.</p>
                                        <p class="text-xs text-slate-500 mt-1">Anda bisa memasukkan URL GitHub secara manual di bawah, atau periksa halaman Profil untuk sinkronisasi ulang.</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <div>
                                    <label for="thumbnail" class="block text-sm font-semibold text-slate-900 mb-1">Gambar Sampul Proyek <span class="text-red-500">*</span></label>
                                    <input type="file" wire:model="thumbnail" id="thumbnail" accept=".jpg,.jpeg,.png,.webp" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-xl bg-white focus:outline-none">
                                    <p class="mt-1 text-xs text-slate-500">Format JPG, PNG, WebP — maksimal 2MB.</p>
                                    
                                    {{-- Loading Indicator --}}
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

    <!-- Modal Detail & Feedback Proyek -->
    @if($isDetailModalOpen && $selectedProjectDetail)
    <div class="relative z-50" aria-labelledby="modal-detail-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity animate-fade-in"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl ring-1 ring-slate-200 animate-scale-up">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full @if($selectedProjectDetail->status === 'approved') bg-emerald-50 text-emerald-600 @elseif($selectedProjectDetail->status === 'rejected') bg-rose-50 text-rose-600 @else bg-blue-50 text-blue-600 @endif">
                                @if($selectedProjectDetail->status === 'approved')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @elseif($selectedProjectDetail->status === 'rejected')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @else
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-detail-title">{{ $selectedProjectDetail->title }}</h3>
                                <div class="mt-1 flex items-center gap-2 flex-wrap">
                                    <span class="text-sm text-slate-500">Status: <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $selectedProjectDetail->status)) }}</span></span>
                                    @php
                                        $visBadges = [
                                            'public' => ['Publik', 'bg-emerald-50 text-emerald-700', 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'],
                                            'restricted' => ['Terbatas', 'bg-violet-50 text-violet-700', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                            'private' => ['Privat', 'bg-slate-100 text-slate-600', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z']
                                        ];
                                        $vis = $visBadges[$selectedProjectDetail->visibility ?? 'public'] ?? $visBadges['public'];
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $vis[1] }}">
                                        <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $vis[2] }}"/></svg>
                                        {{ $vis[0] }}
                                    </span>
                                </div>
                                @if($selectedProjectDetail->tech_stack && count($selectedProjectDetail->tech_stack) > 0)
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($selectedProjectDetail->tech_stack as $tech)
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $tech }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-6">
                            @if($selectedProjectDetail->thumbnail_path)
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Gambar Sampul</h4>
                                    <img src="{{ asset('storage/' . $selectedProjectDetail->thumbnail_path) }}" class="rounded-xl border border-slate-200 w-full object-cover max-h-64 shadow-sm" alt="Thumbnail">
                                </div>
                            @endif
                            @if($selectedProjectDetail->status === 'rejected')
                                <div class="rounded-xl bg-rose-50 p-4 border border-rose-100 text-rose-700">
                                    <h4 class="text-sm font-bold flex items-center mb-1"><svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Proyek Memerlukan Revisi</h4>
                                    <p class="text-sm">{{ $selectedProjectDetail->rejection_reason ?? 'Tidak ada catatan khusus.' }}</p>
                                </div>
                            @endif

                            @if($selectedProjectDetail->validation)
                                <!-- Scoring Section -->
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 mb-3 border-b border-slate-100 pb-2">Rincian Penilaian Guru</h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                        <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                                            <div class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->functionality_score ?? '-' }}</div>
                                            <div class="text-xs text-slate-500 font-medium mt-1">Fungsionalitas</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                                            <div class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->code_quality_score ?? '-' }}</div>
                                            <div class="text-xs text-slate-500 font-medium mt-1">Kualitas Kode</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                                            <div class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->documentation_score ?? '-' }}</div>
                                            <div class="text-xs text-slate-500 font-medium mt-1">Dokumentasi</div>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                                            <div class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->originality_score ?? '-' }}</div>
                                            <div class="text-xs text-slate-500 font-medium mt-1">Orisinalitas</div>
                                        </div>
                                    </div>
                                </div>
                                @if($selectedProjectDetail->status === 'approved' && $selectedProjectDetail->validation->notes)
                                <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-100 text-emerald-800">
                                    <h4 class="text-xs font-bold uppercase tracking-wider mb-1 opacity-70">Feedback Guru</h4>
                                    <p class="text-sm">{{ $selectedProjectDetail->validation->notes }}</p>
                                </div>
                                @endif
                            @endif

                            <!-- Catatan & Feedback Guru (Comments) -->
                            @if(count($detailComments) > 0)
                            <div class="border-t border-slate-200 pt-5">
                                <h4 class="text-sm font-semibold text-slate-900 mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    Catatan & Feedback Guru
                                    <span class="ml-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ count($detailComments) }}</span>
                                </h4>
                                <div class="space-y-3 max-h-60 overflow-y-auto pr-1" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                                    @foreach($detailComments as $comment)
                                    <div class="rounded-xl p-3.5 border {{ $comment['is_pinned'] ? 'bg-amber-50/60 border-amber-200/60 ring-1 ring-amber-100' : 'bg-white border-slate-200/60' }}">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs flex-shrink-0 overflow-hidden">
                                                    <x-avatar :url="$comment['teacher']['user']['avatar_url'] ?? null" :name="$comment['teacher']['user']['name'] ?? 'Guru'" />
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-semibold text-slate-900">{{ $comment['teacher']['user']['name'] ?? 'Guru' }}</span>
                                                    <span class="text-xs text-slate-400 ml-1">&bull; {{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                                @php
                                                    $typeBadges = [
                                                        'general' => 'bg-slate-100 text-slate-600',
                                                        'code_review' => 'bg-violet-100 text-violet-700',
                                                        'requirement' => 'bg-amber-100 text-amber-700',
                                                        'suggestion' => 'bg-blue-100 text-blue-700',
                                                    ];
                                                    $typeLabels = [
                                                        'general' => 'Umum',
                                                        'code_review' => 'Code Review',
                                                        'requirement' => 'Requirement',
                                                        'suggestion' => 'Saran',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $typeBadges[$comment['comment_type']] ?? 'bg-slate-100 text-slate-600' }}">{{ $typeLabels[$comment['comment_type']] ?? $comment['comment_type'] }}</span>
                                                @if($comment['is_pinned'])
                                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2h2a1 1 0 010 2h-1.382l-.724 5.447A2 2 0 0112.918 16h-5.836a2 2 0 01-1.976-1.553L4.382 9H3a1 1 0 010-2h2V5z"/></svg>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="text-sm text-slate-700 mt-2 leading-relaxed">{{ $comment['content'] }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                        @if($selectedProjectDetail->status === 'rejected')
                        <button type="button" wire:click="resubmitProject" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:ml-3 sm:w-auto transition">
                            Kirim Ulang Proyek
                        </button>
                        @endif
                        <button type="button" wire:click="closeDetailModal" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Kelola Dokumentasi -->
    @if($isDocModalOpen && $docProjectId)
    <div class="relative z-50" aria-labelledby="modal-doc-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity animate-fade-in"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl ring-1 ring-slate-200 animate-scale-up">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-violet-50">
                                <svg class="h-6 w-6 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-doc-title">Kelola Dokumentasi</h3>
                                <p class="mt-1 text-sm text-slate-500">Upload video tutorial, PDF, atau dokumen pendukung lainnya.</p>
                            </div>
                        </div>

                        @if(session()->has('doc-success'))
                        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('doc-success') }}
                        </div>
                        @endif

                        <!-- Upload Form -->
                        <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-200/60 mb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Dokumen</label>
                                    <select wire:model="docType" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="pdf">PDF</option>
                                        <option value="video">Video</option>
                                        <option value="image">Gambar</option>
                                        <option value="presentation">Presentasi</option>
                                        <option value="spreadsheet">Spreadsheet</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas</label>
                                    <select wire:model="docIsPublic" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="1">Publik</option>
                                        <option value="0">Privat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi (Opsional)</label>
                                <input type="text" wire:model="docDescription" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Misal: Video demo aplikasi">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">File (Maks. 10MB)</label>
                                    <input type="file" wire:model="docFile" class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" accept=".pdf,.mp4,.avi,.mov,.png,.jpg,.jpeg,.webp,.xlsx,.pptx,.docx">
                                    @error('docFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <button wire:click="uploadDocument" wire:loading.attr="disabled" wire:target="uploadDocument,docFile" type="button" class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-500 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="uploadDocument,docFile">Upload</span>
                                    <span wire:loading wire:target="uploadDocument,docFile" class="inline-flex items-center gap-1.5">
                                        <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        ...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Document List -->
                        @if(count($projectDocs) > 0)
                        <div class="space-y-2 max-h-48 overflow-y-auto" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                            @foreach($projectDocs as $doc)
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 hover:bg-slate-50 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    @php
                                        $docIcons = [
                                            'pdf' => 'text-rose-500',
                                            'video' => 'text-violet-500',
                                            'image' => 'text-blue-500',
                                            'presentation' => 'text-amber-500',
                                            'spreadsheet' => 'text-emerald-500',
                                            'other' => 'text-slate-500',
                                        ];
                                    @endphp
                                    <div class="flex-shrink-0 h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 {{ $docIcons[$doc['doc_type']] ?? 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">{{ $doc['file_name'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ ucfirst($doc['doc_type']) }}
                                            &bull; {{ number_format($doc['file_size'] / 1024, 0) }} KB
                                            @if(!$doc['is_public']) &bull; <span class="text-amber-600">Privat</span> @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="{{ asset('storage/' . $doc['file_path']) }}" target="_blank" class="text-blue-600 hover:text-blue-800 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <button wire:click="deleteDocument({{ $doc['id'] }})" wire:confirm="Yakin ingin menghapus dokumen ini?" type="button" class="text-rose-500 hover:text-rose-700 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-6 text-slate-400">
                            <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-xs">Belum ada dokumen untuk proyek ini.</p>
                        </div>
                        @endif
                    </div>
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                        <button type="button" wire:click="closeDocModal" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto transition">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Kelola Tautan -->
    @if($isLinkModalOpen && $linkProjectId)
    <div class="relative z-50" aria-labelledby="modal-link-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity animate-fade-in"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl ring-1 ring-slate-200 animate-scale-up">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                                <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-link-title">Kelola Tautan (Link)</h3>
                                <p class="mt-1 text-sm text-slate-500">Tambahkan URL demo, YouTube, atau tautan eksternal lainnya.</p>
                            </div>
                        </div>

                        @if(session()->has('link-success'))
                        <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('link-success') }}
                        </div>
                        @endif

                        <!-- Link Form -->
                        <div class="rounded-xl bg-slate-50/80 p-4 border border-slate-200/60 mb-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Tautan</label>
                                    <select wire:model="linkType" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="live_demo">Live Demo</option>
                                        <option value="video_tutorial">Video Tutorial</option>
                                        <option value="documentation">Dokumentasi (API/Web)</option>
                                        <option value="design">Desain (Figma dsb)</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas</label>
                                    <select wire:model="linkIsPublic" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="1">Publik</option>
                                        <option value="0">Privat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Pendek</label>
                                <input type="text" wire:model="linkDescription" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Misal: Link Aplikasi Android">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">URL / Link <span class="text-rose-500">*</span></label>
                                    <input type="url" wire:model="linkUrl" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="https://...">
                                    @error('linkUrl') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <button wire:click="addLink" wire:loading.attr="disabled" wire:target="addLink" type="button" class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="addLink">Tambah</span>
                                    <span wire:loading wire:target="addLink" class="inline-flex items-center gap-1.5">
                                        <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                        ...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Link List -->
                        @if(count($projectLinks) > 0)
                        <div class="space-y-2 max-h-48 overflow-y-auto" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                            @foreach($projectLinks as $link)
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 hover:bg-slate-50 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    @php
                                        $linkIcons = [
                                            'live_demo' => 'text-blue-500',
                                            'video_tutorial' => 'text-rose-500',
                                            'documentation' => 'text-emerald-500',
                                            'design' => 'text-amber-500',
                                            'other' => 'text-slate-500',
                                        ];
                                        $linkLabels = [
                                            'live_demo' => 'Live Demo',
                                            'video_tutorial' => 'Video',
                                            'documentation' => 'Dokumentasi',
                                            'design' => 'Desain UI',
                                            'other' => 'Lainnya',
                                        ];
                                    @endphp
                                    <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 {{ $linkIcons[$link['url_type']] ?? 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ $link['url'] }}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline truncate block">{{ $link['description'] ?: $link['url'] }}</a>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ $linkLabels[$link['url_type']] ?? $link['url_type'] }}</span>
                                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-medium {{ $link['is_public'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $link['is_public'] ? 'Publik' : 'Privat' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="deleteLink({{ $link['id'] }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition ml-2" title="Hapus Link">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300">
                            <p class="text-sm font-medium text-slate-500">Belum ada tautan yang ditambahkan.</p>
                        </div>
                        @endif
                    </div>
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                        <button type="button" wire:click="closeLinkModal" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
