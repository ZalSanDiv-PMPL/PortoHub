<?php

use Livewire\Volt\Component;
use App\Models\Project;

new class extends Component {
    public $selectedProject = null;
    public $showReviewModal = false;
    public $validationNotes = '';

    public function with()
    {
        $teacher = auth()->user()->teacher;
        
        if (!$teacher) {
            return [
                'siswaDiampu' => 0,
                'menungguValidasi' => 0,
                'totalKelas' => 0,
                'antreanProyek' => collect()
            ];
        }

        $siswaDiampu = $teacher->students()->count();
        $totalKelas = $teacher->classAssignments()->distinct('class')->count();
        
        $studentIds = $teacher->students()->pluck('students.id');
        
        $antreanProyek = Project::whereIn('student_id', $studentIds)
            ->whereIn('status', ['submitted', 'under_review'])
            ->with(['student.user', 'student.classAssignments' => function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            }])
            ->orderBy('submission_date', 'asc')
            ->get();
            
        $menungguValidasi = $antreanProyek->count();

        return [
            'siswaDiampu' => $siswaDiampu,
            'menungguValidasi' => $menungguValidasi,
            'totalKelas' => $totalKelas,
            'antreanProyek' => $antreanProyek,
        ];
    }

    public function openReviewModal($projectId)
    {
        $this->selectedProject = Project::with(['student.user', 'student.classAssignments'])->find($projectId);
        $this->validationNotes = '';
        $this->showReviewModal = true;
    }

    public function closeReviewModal()
    {
        $this->showReviewModal = false;
        $this->selectedProject = null;
        $this->validationNotes = '';
    }

    public function approveProject()
    {
        if (!$this->selectedProject) return;
        
        $this->selectedProject->update([
            'status' => 'approved',
            'approval_date' => now(),
            'rejection_reason' => null
        ]);

        \App\Models\Validation::updateOrCreate(
            ['project_id' => $this->selectedProject->id],
            [
                'teacher_id' => auth()->user()->teacher->id,
                'is_approved' => true,
                'validation_date' => now(),
                'notes' => $this->validationNotes,
                'functionality_score' => 90, // Default MVP score
                'code_quality_score' => 90,
                'documentation_score' => 90,
                'originality_score' => 90,
            ]
        );

        $this->closeReviewModal();
    }

    public function rejectProject()
    {
        if (!$this->selectedProject) return;
        
        $this->selectedProject->update([
            'status' => 'rejected',
            'rejection_reason' => $this->validationNotes,
            'approval_date' => null
        ]);

        \App\Models\Validation::updateOrCreate(
            ['project_id' => $this->selectedProject->id],
            [
                'teacher_id' => auth()->user()->teacher->id,
                'is_approved' => false,
                'validation_date' => now(),
                'notes' => $this->validationNotes,
                'functionality_score' => 50,
                'code_quality_score' => 50,
                'documentation_score' => 50,
                'originality_score' => 50,
            ]
        );

        $this->closeReviewModal();
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Welcome Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-6 rounded-2xl bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard Guru</h2>
            <p class="mt-2 text-slate-600">Selamat datang, {{ explode(' ', auth()->user()->name)[0] }}. Kelola kelas dan periksa progres siswa Anda.</p>
        </div>
        <div class="flex items-center space-x-3">
            <select class="rounded-xl border-slate-200 bg-white/80 backdrop-blur-md px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition hover:bg-white">
                <option value="">Semua Kelas</option>
                <!-- Opsi kelas akan dimuat dinamis nanti -->
                <option value="X RPL A">X RPL A</option>
                <option value="X RPL B">X RPL B</option>
            </select>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-8">
        <!-- Card 1 -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-indigo-50/80 p-3 ring-1 ring-indigo-100/50">
                    <svg class="h-6 w-6 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Siswa Diampu</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $siswaDiampu }}</p>
            </dd>
        </div>

        <!-- Card 2 -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-rose-50/80 p-3 ring-1 ring-rose-100/50">
                    <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Menunggu Validasi</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $menungguValidasi }}</p>
            </dd>
        </div>

        <!-- Card 3 -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-blue-50/80 p-3 ring-1 ring-blue-100/50">
                    <svg class="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Kelas</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalKelas }}</p>
            </dd>
        </div>
    </div>

    <!-- Daftar Validasi Proyek (Table) -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h3 class="text-lg font-bold text-slate-900">Antrean Validasi Proyek</h3>
        <div class="relative w-full sm:w-auto">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" class="block w-full sm:w-64 rounded-xl border-slate-200 bg-white/70 backdrop-blur-md py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm transition hover:bg-white/90" placeholder="Cari siswa atau proyek...">
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-hidden rounded-2xl bg-white/60 backdrop-blur-xl shadow-sm ring-1 ring-slate-200/50 border border-white/50">
        <div class="min-w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/60 text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50/50 backdrop-blur-md">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold text-slate-900">Nama Siswa</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-slate-900">Kelas</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-slate-900">Judul Proyek</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-slate-900">Tanggal Submit</th>
                        <th scope="col" class="px-6 py-4 font-semibold text-slate-900 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60 bg-transparent">
                    @forelse($antreanProyek as $proyek)
                        <tr class="transition hover:bg-white/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                            {{ substr($proyek->student->user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-slate-900">{{ $proyek->student->user->name }}</div>
                                        <div class="text-slate-500 text-xs">{{ $proyek->student->nis }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                {{ $proyek->student->classAssignments->first()->class ?? $proyek->student->class }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-900 font-medium">{{ $proyek->title }}</div>
                                <div class="text-xs text-slate-500">
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        {{ ucfirst(str_replace('_', ' ', $proyek->status)) }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                {{ $proyek->submission_date ? \Carbon\Carbon::parse($proyek->submission_date)->diffForHumans() : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <button wire:click="openReviewModal({{ $proyek->id }})" class="text-blue-600 hover:text-blue-900 font-semibold bg-blue-50 px-3 py-1.5 rounded-lg transition hover:bg-blue-100">Review</button>
                            </td>
                        </tr>
                    @empty
                    <!-- Empty State for Table -->
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-slate-900">Belum ada antrean</h3>
                            <p class="mt-1 text-sm text-slate-500">Belum ada proyek siswa yang menunggu untuk divalidasi.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Review & Validasi -->
    <div x-data="{ open: @entangle('showReviewModal') }" 
         x-show="open" 
         style="display: none;" 
         class="relative z-50" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
         
        <!-- Backdrop -->
        <div x-show="open" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity"></div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     @click.away="$wire.closeReviewModal()"
                     class="relative transform overflow-hidden rounded-2xl bg-white/90 backdrop-blur-xl border border-white/50 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl ring-1 ring-slate-200/50">
                     
                    @if($selectedProject)
                    <div class="p-6 sm:p-8">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xl">
                                {{ substr($selectedProject->student->user->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" id="modal-title">{{ $selectedProject->title }}</h3>
                                <p class="text-sm text-slate-500">Oleh: {{ $selectedProject->student->user->name }} • {{ $selectedProject->student->classAssignments->first()->class ?? $selectedProject->student->class }}</p>
                            </div>
                        </div>

                        <!-- Project Details -->
                        <div class="space-y-4 mb-8">
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Deskripsi Proyek</h4>
                                <p class="text-sm text-slate-700">{{ $selectedProject->description }}</p>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <a href="{{ $selectedProject->github_url }}" target="_blank" class="inline-flex items-center space-x-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 px-4 py-2 rounded-xl hover:bg-slate-50 transition">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                    </svg>
                                    <span>Lihat Repositori GitHub</span>
                                    <svg class="h-4 w-4 ml-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <span class="text-xs font-medium bg-blue-50 text-blue-700 px-2.5 py-1.5 rounded-lg border border-blue-100">
                                    Model: {{ ucfirst($selectedProject->development_model) }}
                                </span>
                            </div>
                        </div>

                        <!-- Feedback Form -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Catatan Validasi & Feedback (Wajib untuk Penolakan)</label>
                            <textarea wire:model="validationNotes" rows="4" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm transition" placeholder="Tuliskan catatan Anda di sini..."></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-slate-50/50 backdrop-blur-md px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 gap-2 sm:gap-0 border-t border-slate-200/60 rounded-b-2xl">
                        <button wire:click="closeReviewModal" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300/50 hover:bg-white transition">
                            Tutup
                        </button>
                        <button wire:click="rejectProject" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl bg-rose-50/80 text-rose-700 border border-rose-200/50 px-4 py-2.5 text-sm font-semibold hover:bg-rose-100 transition">
                            Tolak (Revisi)
                        </button>
                        <button wire:click="approveProject" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                            Terima & Validasi
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
