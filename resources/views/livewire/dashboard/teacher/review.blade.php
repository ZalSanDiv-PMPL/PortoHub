<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Project;
use App\Models\Comment;

new #[Layout('layouts.app')] class extends Component {
    public Project $project;

    public $validationNotes = '';
    public bool $isEditingScore = false;
    
    // Scoring rubrics (0-100)
    public int $functionalityScore = 80;
    public int $codeQualityScore = 80;
    public int $documentationScore = 80;
    public int $originalityScore = 80;

    // Comment system
    public string $commentContent = '';
    public string $commentType = 'general';
    public $projectComments = [];

    public function mount(Project $project)
    {
        $this->project = $project;
        
        // Ensure only the assigned teacher can access this project
        if ($this->project->student->classAssignments()->where('teacher_id', auth()->user()->teacher->id)->doesntExist()) {
            abort(403, 'Anda tidak berhak me-review proyek ini.');
        }

        // Set status to under_review if it was submitted
        if ($this->project->status === 'submitted') {
            $this->project->update(['status' => 'under_review']);
        }

        $this->loadComments();
    }

    public function loadComments()
    {
        $this->projectComments = Comment::where('project_id', $this->project->id)
            ->with(['teacher.user', 'student.user'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function addComment()
    {
        $this->validate([
            'commentContent' => 'required|string|min:3',
            'commentType' => 'required|in:general,code_review,requirement,suggestion',
        ], [
            'commentContent.required' => 'Isi komentar wajib diisi.',
            'commentContent.min' => 'Komentar minimal 3 karakter.',
        ]);

        Comment::create([
            'project_id' => $this->project->id,
            'teacher_id' => auth()->user()->teacher->id,
            'content' => $this->commentContent,
            'comment_type' => $this->commentType,
            'status' => 'pending',
            'is_pinned' => false,
        ]);

        $this->project->student->user->notify(new \App\Notifications\NewCommentNotification($this->project, auth()->user()->teacher));

        $this->commentContent = '';
        $this->commentType = 'general';
        $this->loadComments();
    }

    public function togglePinComment($commentId)
    {
        $comment = Comment::find($commentId);
        if ($comment && $comment->teacher_id === auth()->user()->teacher->id) {
            $comment->update(['is_pinned' => !$comment->is_pinned]);
            $this->loadComments();
        }
    }

    /**
     * Simpan data validasi ke tabel validations.
     */
    private function saveValidation(bool $isApproved): void
    {
        \App\Models\Validation::updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'teacher_id' => auth()->user()->teacher->id,
                'is_approved' => $isApproved,
                'validation_date' => now(),
                'notes' => $this->validationNotes,
                'functionality_score' => $this->functionalityScore,
                'code_quality_score' => $this->codeQualityScore,
                'documentation_score' => $this->documentationScore,
                'originality_score' => $this->originalityScore,
            ]
        );
    }

    public function enableScoreEditing()
    {
        $this->isEditingScore = true;
    }

    public function approveProject()
    {
        if ($this->project->status === 'approved' && !$this->isEditingScore) return;

        $isUpdate = $this->project->status === 'approved';

        $this->validate([
            'validationNotes' => 'required|string|min:5',
            'functionalityScore' => 'required|integer|min:0|max:100',
            'codeQualityScore' => 'required|integer|min:0|max:100',
            'documentationScore' => 'required|integer|min:0|max:100',
            'originalityScore' => 'required|integer|min:0|max:100',
        ], [
            'validationNotes.required' => 'Catatan validasi wajib diisi.',
            'validationNotes.min' => 'Catatan validasi minimal 5 karakter.',
            'functionalityScore.required' => 'Skor fungsionalitas wajib diisi.',
            'functionalityScore.min' => 'Skor minimal 0.',
            'functionalityScore.max' => 'Skor maksimal 100.',
            'codeQualityScore.required' => 'Skor kualitas kode wajib diisi.',
            'documentationScore.required' => 'Skor dokumentasi wajib diisi.',
            'originalityScore.required' => 'Skor orisinalitas wajib diisi.',
        ]);
        
        $updateData = [
            'status' => 'approved',
            'rejection_reason' => null
        ];
        
        if (!$isUpdate) {
            $updateData['approval_date'] = now();
        }

        $this->project->update($updateData);

        $this->saveValidation(true);
        
        if (!$isUpdate) {
            $this->project->student->user->notify(new \App\Notifications\ProjectStatusUpdated($this->project));
            session()->flash('success', 'Proyek berhasil disetujui.');
        } else {
            session()->flash('success', 'Penilaian proyek berhasil diperbarui.');
        }
        
        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function rejectProject()
    {
        if ($this->project->status === 'approved') return;

        // Only notes are required for rejection
        $this->validate([
            'validationNotes' => 'required|string|min:5',
        ], [
            'validationNotes.required' => 'Catatan validasi (alasan penolakan) wajib diisi.',
            'validationNotes.min' => 'Catatan validasi minimal 5 karakter.',
        ]);
        
        $this->project->update([
            'status' => 'rejected',
            'rejection_reason' => $this->validationNotes,
            'approval_date' => null
        ]);

        $this->saveValidation(false);
        $this->project->student->user->notify(new \App\Notifications\ProjectStatusUpdated($this->project));
        
        session()->flash('success', 'Proyek ditolak untuk direvisi.');
        return $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a wire:navigate href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-700 mb-2 transition">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </a>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Review Proyek: {{ $project->title }}</h2>
        </div>
        <div>
            <span class="inline-flex items-center rounded-md px-3 py-1 text-sm font-medium ring-1 ring-inset {{ $project->status === 'approved' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : ($project->status === 'rejected' ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20') }}">
                Status: {{ $project->status === 'rejected' ? 'Menunggu Revisi' : ucfirst(str_replace('_', ' ', $project->status)) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Kolom Kiri: Detail Proyek -->
        <div class="space-y-6">
            <div class="bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl p-6">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xl overflow-hidden">
                        <x-avatar :url="$project->student->user->avatar_url" :name="$project->student->user->name" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">{{ $project->student->user->name }}</h3>
                        <p class="text-sm text-slate-500">{{ $project->student->nis }} • Kelas: {{ $project->student->active_class }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @if($project->thumbnail_path)
                        <div class="mb-4">
                            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Gambar Sampul</h4>
                            <img src="{{ asset('storage/' . $project->thumbnail_path) }}" class="rounded-xl border border-slate-200 w-full object-cover max-h-64 shadow-sm" alt="Thumbnail">
                        </div>
                    @endif
                    <div>
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Deskripsi Proyek</h4>
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 text-sm text-slate-700 leading-relaxed">
                            {{ $project->description }}
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 pt-2">
                        <a href="{{ $project->github_url }}" target="_blank" class="inline-flex items-center space-x-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 px-4 py-2 rounded-xl hover:bg-slate-50 transition">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            <span>Lihat Repositori GitHub</span>
                            <svg class="h-4 w-4 ml-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-3 py-2 rounded-xl border border-blue-100">
                            Model: {{ ucfirst($project->development_model) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Penilaian & Komentar -->
        <div class="space-y-6">
            <!-- Form Penilaian -->
            <div class="bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2">Rubrik Penilaian & Validasi</h3>
                
                @if($project->status === 'under_review' || $isEditingScore)
                    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Fungsionalitas (0-100)</label>
                            <input type="number" min="0" max="100" wire:model="functionalityScore" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm">
                            @error('functionalityScore') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kualitas Kode (0-100)</label>
                            <input type="number" min="0" max="100" wire:model="codeQualityScore" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm">
                            @error('codeQualityScore') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Dokumentasi (0-100)</label>
                            <input type="number" min="0" max="100" wire:model="documentationScore" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm">
                            @error('documentationScore') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Orisinalitas (0-100)</label>
                            <input type="number" min="0" max="100" wire:model="originalityScore" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm">
                            @error('originalityScore') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-slate-900 mb-2">Catatan Validasi & Feedback</label>
                        <textarea wire:model="validationNotes" rows="3" class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm transition" placeholder="Tuliskan catatan evaluasi untuk proyek ini..."></textarea>
                        @error('validationNotes') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        @if($project->status !== 'approved')
                        <button wire:click="rejectProject" wire:loading.attr="disabled" wire:target="rejectProject" type="button" class="w-full flex-1 inline-flex justify-center items-center rounded-xl bg-rose-50/80 text-rose-700 border border-rose-200/50 px-4 py-2.5 text-sm font-semibold hover:bg-rose-100 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="rejectProject">Tolak (Revisi)</span>
                            <span wire:loading.inline-flex wire:target="rejectProject" class="items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Memproses...
                            </span>
                        </button>
                        @else
                        <button wire:click="$set('isEditingScore', false)" type="button" class="w-full flex-1 inline-flex justify-center items-center rounded-xl bg-white border border-slate-300 text-slate-700 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 transition">
                            Batal Edit
                        </button>
                        @endif
                        <button wire:click="approveProject" wire:loading.attr="disabled" wire:target="approveProject" type="button" class="w-full flex-1 inline-flex justify-center items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="approveProject">{{ $isEditingScore && $project->status === 'approved' ? 'Perbarui Penilaian' : 'Terima & Validasi' }}</span>
                            <span wire:loading.inline-flex wire:target="approveProject" class="items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                @elseif($project->status === 'approved' && !$isEditingScore)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-emerald-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-sm font-bold text-emerald-800">Proyek Telah Disetujui</h4>
                        <p class="text-xs text-emerald-600 mt-1 mb-4">Anda sudah memberikan validasi dan penilaian akhir pada proyek ini.</p>
                        <button wire:click="enableScoreEditing" type="button" class="inline-flex items-center justify-center rounded-xl bg-white border border-emerald-200 px-4 py-2 text-xs font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50 transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Ralat Penilaian
                        </button>
                    </div>
                @elseif($project->status === 'rejected')
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-amber-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <h4 class="text-sm font-bold text-amber-800">Proyek Sedang Direvisi Siswa</h4>
                        <p class="text-xs text-amber-600 mt-1">Siswa masih melakukan perbaikan. Anda tetap dapat melanjutkan diskusi di kolom bawah.</p>
                    </div>
                @endif
            </div>

            <!-- Komentar & Diskusi -->
            <div class="bg-white/60 backdrop-blur-xl border border-white/50 shadow-sm ring-1 ring-slate-200/50 rounded-2xl p-6 flex flex-col" style="height: 500px;">
                <h3 class="text-lg font-bold text-slate-900 mb-4 border-b border-slate-200 pb-2 flex items-center justify-between">
                    <span>Diskusi Proyek</span>
                    @if(count($projectComments) > 0)
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ count($projectComments) }} Komentar</span>
                    @endif
                </h3>
                
                <!-- History -->
                <div class="flex-1 overflow-y-auto pr-2 space-y-4 mb-4" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                    @forelse($projectComments as $comment)
                        <div class="rounded-xl p-3.5 border transition {{ $comment['is_pinned'] ? 'bg-amber-50/60 border-amber-200/60 ring-1 ring-amber-100' : 'bg-white/60 border-slate-200/60' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($comment['student_id'])
                                        <div class="h-7 w-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs flex-shrink-0 overflow-hidden">
                                            <x-avatar :url="$comment['student']['user']['avatar_url'] ?? null" :name="$comment['student']['user']['name'] ?? 'Siswa'" />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-semibold text-slate-900">{{ $comment['student']['user']['name'] ?? 'Siswa' }}</span>
                                            <span class="text-xs text-slate-400 ml-1">• {{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        <div class="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs flex-shrink-0 overflow-hidden">
                                            <x-avatar :url="$comment['teacher']['user']['avatar_url'] ?? null" :name="$comment['teacher']['user']['name'] ?? 'Guru'" />
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-semibold text-slate-900">{{ $comment['teacher']['user']['name'] ?? 'Guru' }} (Saya)</span>
                                            <span class="text-xs text-slate-400 ml-1">• {{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}</span>
                                        </div>
                                    @endif
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
                                            'general' => $comment['student_id'] ? 'Balasan' : 'Umum',
                                            'code_review' => 'Code Review',
                                            'requirement' => 'Requirement',
                                            'suggestion' => 'Saran',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $typeBadges[$comment['comment_type']] ?? 'bg-slate-100 text-slate-600' }}">{{ $typeLabels[$comment['comment_type']] ?? $comment['comment_type'] }}</span>
                                    @if($comment['is_pinned'])
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2h2a1 1 0 010 2h-1.382l-.724 5.447A2 2 0 0112.918 16h-5.836a2 2 0 01-1.976-1.553L4.382 9H3a1 1 0 010-2h2V5z"/></svg>
                                    @endif
                                    @if(($comment['teacher_id'] ?? null) === auth()->user()->teacher?->id)
                                    <button wire:click="togglePinComment({{ $comment['id'] }})" type="button" class="text-slate-400 hover:text-amber-500 transition" title="{{ $comment['is_pinned'] ? 'Lepas pin' : 'Pin komentar' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <p class="text-sm text-slate-700 mt-2 leading-relaxed">{{ $comment['content'] }}</p>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-slate-400">
                            <svg class="h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            <p class="text-sm">Belum ada diskusi untuk proyek ini.</p>
                        </div>
                    @endforelse
                </div>
                
                <!-- Add Comment Form -->
                <div class="pt-4 border-t border-slate-200">
                    <div class="flex items-center gap-3 mb-3">
                        <select wire:model="commentType" class="rounded-lg border-slate-200 bg-white text-xs font-medium py-1.5 px-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="general">Umum</option>
                            <option value="code_review">Code Review</option>
                            <option value="requirement">Requirement</option>
                            <option value="suggestion">Saran</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <textarea wire:model="commentContent" rows="2" class="flex-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm transition" placeholder="Tulis komentar..."></textarea>
                        <button wire:click="addComment" wire:loading.attr="disabled" wire:target="addComment" type="button" class="self-end inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="addComment">Kirim</span>
                            <span wire:loading.inline-flex wire:target="addComment">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </span>
                        </button>
                    </div>
                    @error('commentContent') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>
</div>
