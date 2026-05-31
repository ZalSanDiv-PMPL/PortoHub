<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public string $filterStatus = 'all';
    
    public bool $isFeedbackModalOpen = false;
    public ?Project $selectedProjectDetail = null;
    public array $detailComments = [];

    public function openFeedbackModal(Project $project)
    {
        $student = auth()->user()->student;
        if (!$student || $project->student_id !== $student->id) {
            abort(403);
        }

        $this->selectedProjectDetail = $project->load('validation');
        
        $this->detailComments = Comment::where('project_id', $project->id)
            ->with('teacher.user')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        // Mark pending as viewed
        Comment::where('project_id', $project->id)
            ->where('status', 'pending')
            ->update(['status' => 'viewed']);

        $this->isFeedbackModalOpen = true;
    }

    public function closeFeedbackModal()
    {
        $this->isFeedbackModalOpen = false;
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
            $this->closeFeedbackModal();
            session()->flash('success', 'Proyek berhasil diajukan ulang untuk direviu.');
        }
    }

    public function with()
    {
        $student = auth()->user()->student;
        
        // Base query for stats
        $allProjects = $student ? $student->projects()->get() : collect();
        
        $totalProyek = $allProjects->count();
        $sedangDireviu = $allProjects->where('status', 'under_review')->count();
        $proyekLulus = $allProjects->where('status', 'approved')->count();

        // Query for displayed projects with unread comments count
        if ($student) {
            $query = $student->projects()
                ->with('validation')
                ->withCount(['comments' => function($q) {
                    $q->where('status', 'pending');
                }])
                ->orderBy('created_at', 'desc');
                
            if ($this->filterStatus !== 'all') {
                $query->where('status', $this->filterStatus);
            }
            
            $projects = $query->get();
            
            // Prioritize rejected projects at the top
            $projects = $projects->sortByDesc(function ($project) {
                return $project->status === 'rejected' ? 1 : 0;
            })->values();
        } else {
            $projects = collect();
        }

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
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Halo, {{ explode(' ', auth()->user()->name)[0]
                }}!</h2>
            <p class="mt-2 text-slate-600">Selamat datang di Dashboard Siswa. Pantau terus progres proyekmu di sini.</p>
        </div>

        @if(auth()->user()->student && auth()->user()->student->is_validated)
        <a wire:navigate href="{{ route('projects.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 group">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 transition-transform group-hover:scale-110"
                viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
            </svg>
            Ajukan Proyek Baru
        </a>
        @else
        <button disabled
            class="inline-flex items-center justify-center rounded-xl bg-slate-300 cursor-not-allowed px-5 py-2.5 text-sm font-semibold text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
            </svg>
            Ajukan Proyek Baru (Terkunci)
        </button>
        @endif
    </div>

    @if(auth()->user()->student && empty(auth()->user()->student->nis))
    <!-- NIS Warning Banner -->
    <div
        class="mb-8 p-4 rounded-xl bg-amber-50/80 backdrop-blur-md border border-amber-200 text-amber-800 flex items-start sm:items-center justify-between">
        <div class="flex items-start sm:items-center">
            <svg class="w-6 h-6 mr-3 flex-shrink-0 text-amber-600 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h4 class="font-bold text-sm">Data Akademik Belum Lengkap</h4>
                <p class="text-sm mt-1 sm:mt-0">Admin tidak dapat memvalidasi akun Anda jika NIS kosong. Mohon lengkapi
                    Data Akademik Anda di pengaturan profil.</p>
            </div>
        </div>
        <a href="{{ route('profile') }}"
            class="ml-4 inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500 whitespace-nowrap">
            Isi NIS Sekarang
        </a>
    </div>
    @elseif(auth()->user()->student && !auth()->user()->student->is_validated)
    <!-- Validation Warning Banner -->
    <div
        class="mb-8 p-4 rounded-xl bg-blue-50/80 backdrop-blur-md border border-blue-200 text-blue-800 flex items-start sm:items-center">
        <svg class="w-6 h-6 mr-3 flex-shrink-0 text-blue-600 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <h4 class="font-bold text-sm">Akun Anda sedang dalam peninjauan.</h4>
            <p class="text-sm mt-1 sm:mt-0">Data Anda sudah lengkap. Admin sekolah perlu menyetujui akun Anda dan
                menempatkan Anda ke dalam kelas sebelum Anda bisa mengirimkan proyek portofolio.</p>
        </div>
    </div>
    @endif

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <!-- Card 1 -->
        <div
            class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-blue-50 p-3">
                    <svg class="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Proyek</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalProyek }}</p>
            </dd>
        </div>

        <!-- Card 2 -->
        <div
            class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-amber-50 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Sedang Direviu</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $sedangDireviu }}</p>
            </dd>
        </div>

        <!-- Card 3 -->
        <div
            class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <dt>
                <div class="absolute rounded-xl bg-emerald-50 p-3">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h3 class="text-lg font-bold text-slate-900">Proyek Saya</h3>
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('filterStatus', 'all')" class="px-3 py-1.5 text-xs font-semibold rounded-full border transition {{ $filterStatus === 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Semua</button>
            <button wire:click="$set('filterStatus', 'under_review')" class="px-3 py-1.5 text-xs font-semibold rounded-full border transition {{ $filterStatus === 'under_review' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Direviu</button>
            <button wire:click="$set('filterStatus', 'approved')" class="px-3 py-1.5 text-xs font-semibold rounded-full border transition {{ $filterStatus === 'approved' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Lulus</button>
            <button wire:click="$set('filterStatus', 'rejected')" class="px-3 py-1.5 text-xs font-semibold rounded-full border transition {{ $filterStatus === 'rejected' ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">Revisi</button>
        </div>
    </div>

    @if($projects->isEmpty() && $filterStatus === 'all')
    <!-- Onboarding Empty State -->
    <div class="rounded-2xl bg-white p-8 sm:p-12 shadow-sm ring-1 ring-slate-200">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">Selamat datang di PortoHub!</h3>
                <p class="mt-2 text-sm text-slate-500">Ikuti langkah-langkah di bawah ini untuk mulai membangun portofolio Anda.</p>
            </div>
            
            <div class="space-y-4">
                <!-- Step 1 -->
                <div class="flex items-start gap-4 p-4 rounded-xl border {{ auth()->user()->student->nis ? 'bg-emerald-50/50 border-emerald-100' : 'bg-white border-slate-200' }}">
                    <div class="mt-0.5 flex-shrink-0">
                        @if(auth()->user()->student->nis)
                            <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <div class="h-6 w-6 rounded-full border-2 border-slate-300 flex items-center justify-center"><span class="text-xs font-bold text-slate-400">1</span></div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-900">Lengkapi Profil & NIS</h4>
                        <p class="text-xs text-slate-500 mt-1">Admin sekolah membutuhkan NIS Anda untuk memvalidasi akun.</p>
                    </div>
                    @if(!auth()->user()->student->nis)
                        <a href="{{ route('profile') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg transition">Isi Profil</a>
                    @endif
                </div>

                <!-- Step 2 -->
                <div class="flex items-start gap-4 p-4 rounded-xl border {{ auth()->user()->githubToken ? 'bg-emerald-50/50 border-emerald-100' : 'bg-white border-slate-200' }}">
                    <div class="mt-0.5 flex-shrink-0">
                        @if(auth()->user()->githubToken)
                            <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <div class="h-6 w-6 rounded-full border-2 border-slate-300 flex items-center justify-center"><span class="text-xs font-bold text-slate-400">2</span></div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-900">Hubungkan GitHub</h4>
                        <p class="text-xs text-slate-500 mt-1">Permudah unggah proyek dengan sinkronisasi otomatis.</p>
                    </div>
                    @if(!auth()->user()->githubToken)
                        <a href="{{ route('profile') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 px-3 py-1.5 rounded-lg transition">Hubungkan</a>
                    @endif
                </div>

                <!-- Step 3 -->
                <div class="flex items-start gap-4 p-4 rounded-xl border bg-white border-slate-200">
                    <div class="mt-0.5 flex-shrink-0">
                        <div class="h-6 w-6 rounded-full border-2 border-slate-300 flex items-center justify-center"><span class="text-xs font-bold text-slate-400">3</span></div>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-slate-900">Unggah Proyek Pertama</h4>
                        <p class="text-xs text-slate-500 mt-1">Kirim proyek Anda untuk ditinjau oleh guru.</p>
                        <div class="mt-3">
                            @if(auth()->user()->student && auth()->user()->student->is_validated)
                                <a wire:navigate href="{{ route('projects.create') }}" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-600">Ajukan Proyek</a>
                            @else
                                <button disabled class="inline-flex items-center rounded-xl bg-slate-300 px-4 py-2 text-xs font-semibold text-white shadow-sm cursor-not-allowed">Menunggu Validasi</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @elseif($projects->isEmpty())
    <div class="rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">Tidak ada proyek yang sesuai dengan filter.</p>
    </div>
    @else
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($projects as $project)
        <div
            class="relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
            <div class="p-6 flex-1">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-sm">
                            <span class="h-1.5 w-1.5 rounded-full 
                                @if($project->status === 'approved') bg-emerald-500
                                @elseif($project->status === 'rejected') bg-rose-500
                                @elseif($project->status === 'under_review') bg-amber-500
                                @else bg-blue-500 @endif
                            "></span>
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                        @if($project->visibility !== 'public')
                        <span
                            class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $project->visibility === 'private' ? 'bg-slate-100 text-slate-600' : 'bg-violet-50 text-violet-600' }} ring-1 ring-inset ring-current/20">
                            @if($project->visibility === 'private')
                            <svg class="w-2.5 h-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Privat
                            @else
                            <svg class="w-2.5 h-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Terbatas
                            @endif
                        </span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-500">{{ $project->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex justify-between items-start gap-4 mb-2">
                    <h3 class="text-lg font-bold text-slate-900">{{ $project->title }}</h3>
                    @if($project->status === 'approved' && $project->validation)
                        @php
                            $avgScore = round(($project->validation->functionality_score + $project->validation->code_quality_score + $project->validation->documentation_score + $project->validation->originality_score) / 4);
                        @endphp
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-bold text-yellow-700 ring-1 ring-inset ring-yellow-600/20 whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            {{ $avgScore }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-600 line-clamp-3">{{ $project->description }}</p>
                @if($project->tech_stack && count($project->tech_stack) > 0)
                <div class="flex flex-wrap gap-1.5 mt-3">
                    @foreach(array_slice($project->tech_stack, 0, 4) as $tech)
                    <span
                        class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{
                        $tech }}</span>
                    @endforeach
                    @if(count($project->tech_stack) > 4)
                    <span class="text-[10px] text-slate-400 font-medium self-center">+{{ count($project->tech_stack) - 4
                        }}</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($project->github_url)
                    <a href="{{ $project->github_url }}" target="_blank" class="text-slate-400 hover:text-slate-700 transition" title="Repository">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    @if($project->status === 'approved' || $project->status === 'rejected')
                        <button type="button" wire:click="openFeedbackModal({{ $project->id }})" class="relative text-sm font-bold {{ $project->status === 'rejected' ? 'text-rose-600 hover:text-rose-800' : 'text-blue-600 hover:text-blue-800' }} inline-flex items-center transition">
                            {{ $project->status === 'rejected' ? 'Lihat Revisi' : 'Lihat Penilaian' }}
                            @if($project->comments_count > 0)
                                <span class="absolute -top-0.5 -right-1 flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            @endif
                        </button>
                    @endif
                    <a wire:navigate href="{{ route('projects.manage', $project->id) }}"
                        class="text-sm font-bold text-slate-700 hover:text-slate-900 inline-flex items-center transition">
                        Kelola Proyek &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Feedback & Validation Modal -->
    @if($isFeedbackModalOpen && $selectedProjectDetail)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/60 p-4 backdrop-blur-sm sm:p-0">
        <div class="relative w-full max-w-2xl scale-100 transform rounded-2xl bg-white text-left align-middle shadow-2xl transition-all sm:my-8 opacity-100">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="text-lg font-bold text-slate-900">
                    {{ $selectedProjectDetail->status === 'rejected' ? 'Catatan Revisi Proyek' : 'Hasil Penilaian Proyek' }}
                </h3>
                <button type="button" wire:click="closeFeedbackModal" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                <!-- Rejection Reason if any -->
                @if($selectedProjectDetail->status === 'rejected' && $selectedProjectDetail->rejection_reason)
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 flex items-start gap-3">
                    <svg class="w-6 h-6 text-rose-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-rose-900">Alasan Penolakan</h4>
                        <p class="text-sm text-rose-700 mt-1">{{ $selectedProjectDetail->rejection_reason }}</p>
                    </div>
                </div>
                @endif

                <!-- Validation Score if Approved -->
                @if($selectedProjectDetail->status === 'approved' && $selectedProjectDetail->validation)
                <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="rounded-xl border border-slate-200 p-4 text-center">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Fungsionalitas</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->functionality_score }}<span class="text-sm text-slate-400 font-medium">/100</span></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4 text-center">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Kualitas Kode</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->code_quality_score }}<span class="text-sm text-slate-400 font-medium">/100</span></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4 text-center">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Dokumentasi</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->documentation_score }}<span class="text-sm text-slate-400 font-medium">/100</span></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4 text-center">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Orisinalitas</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $selectedProjectDetail->validation->originality_score }}<span class="text-sm text-slate-400 font-medium">/100</span></p>
                    </div>
                </div>
                @if($selectedProjectDetail->validation->notes)
                <div class="mb-6 rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <h4 class="text-sm font-bold text-blue-900 mb-2">Catatan Akhir Penilaian</h4>
                    <p class="text-sm text-blue-800">{{ $selectedProjectDetail->validation->notes }}</p>
                </div>
                @endif
                @endif

                <!-- Comments List -->
                <h4 class="text-sm font-bold text-slate-900 mb-4">Umpan Balik Guru</h4>
                @if(count($detailComments) > 0)
                <div class="space-y-4">
                    @foreach($detailComments as $comment)
                    <div class="rounded-xl p-4 sm:p-5 border {{ $comment['is_pinned'] ? 'bg-amber-50/60 border-amber-200/60 ring-1 ring-amber-100' : 'bg-white border-slate-200/60' }}">
                        <div class="flex items-start gap-3">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm flex-shrink-0">
                                {{ substr(explode(' ', $comment['teacher']['user']['name'])[0] ?? 'G', 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-slate-900">{{ $comment['teacher']['user']['name'] ?? 'Guru' }}</span>
                                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}</span>
                                </div>
                                <div class="mt-2 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $comment['content'] }}</div>
                                @if(isset($comment['file_path']) && $comment['file_path'])
                                <div class="mt-3">
                                    <a href="{{ Storage::url($comment['file_path']) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-600 transition">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Lampiran Tersedia
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500 text-sm">
                    Belum ada umpan balik yang diberikan.
                </div>
                @endif
            </div>

            <!-- Footer Action for Rejected -->
            @if($selectedProjectDetail->status === 'rejected')
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 rounded-b-2xl flex justify-end">
                <button type="button" wire:click="resubmitProject" class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 transition">
                    <span wire:loading.remove wire:target="resubmitProject">Ajukan Ulang Proyek</span>
                    <span wire:loading wire:target="resubmitProject">Memproses...</span>
                </button>
            </div>
            @else
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 rounded-b-2xl flex justify-end">
                <button type="button" wire:click="closeFeedbackModal" class="inline-flex items-center justify-center rounded-xl bg-white border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    Tutup
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>