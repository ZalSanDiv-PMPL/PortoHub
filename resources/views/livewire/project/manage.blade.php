<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Project;
use App\Models\Comment;
use App\Models\Documentation;
use App\Models\ProjectUrl;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Project $project;

    // === Edit Project Form ===
    public string $title = '';
    public string $description = '';
    public string $development_model = 'waterfall';
    public string $github_url = '';
    public string $visibility = 'public';
    public $thumbnail;

    // === Docs ===
    public $docFile;
    public string $docType = 'pdf';
    public string $docDescription = '';
    public bool $docIsPublic = true;
    public array $projectDocs = [];

    // === Links ===
    public string $linkUrl = '';
    public string $linkType = 'live_demo';
    public string $linkDescription = '';
    public bool $linkIsPublic = true;
    public array $projectLinks = [];



    public function mount(Project $project)
    {
        $student = auth()->user()->student;
        if (!$student || $project->student_id !== $student->id) {
            abort(403);
        }

        $this->project = $project;
        
        $this->title = $project->title;
        $this->description = $project->description;
        $this->development_model = $project->development_model;
        $this->github_url = $project->github_url;
        $this->visibility = $project->visibility;

        $this->loadDocs();
        $this->loadLinks();
    }

    // === Edit Logic ===
    public function submitProject()
    {
        if ($this->project->status === 'under_review') {
            session()->flash('error', 'Proyek yang sedang direview tidak dapat diubah.');
            return;
        }

        if ($this->project->status === 'approved') {
            $this->validate([
                'visibility' => 'required|in:public,restricted,private',
            ]);
            $this->project->visibility = $this->visibility;
            $this->project->save();
            session()->flash('success', 'Visibilitas proyek berhasil disimpan.');
            return;
        }

        $this->validate([
            'title' => 'required|min:5',
            'description' => 'required|min:15',
            'development_model' => 'required|in:waterfall,agile,other',
            'visibility' => 'required|in:public,restricted,private',
            'github_url' => 'required|url',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'thumbnail.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
            'thumbnail.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'thumbnail.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',
        ]);

        if ($this->thumbnail) {
            if ($this->project->thumbnail_path) {
                Storage::disk('public')->delete($this->project->thumbnail_path);
            }
            $this->project->thumbnail_path = $this->thumbnail->store('thumbnails/' . $this->project->student_id, 'public');
        }

        $this->project->title = $this->title;
        $this->project->description = $this->description;
        $this->project->development_model = $this->development_model;
        
        $githubChanged = $this->project->github_url !== $this->github_url;
        $this->project->github_url = $this->github_url;
        $this->project->visibility = $this->visibility;
        $this->project->save();

        if ($githubChanged && $this->github_url) {
            \Illuminate\Support\Facades\Artisan::call('github:sync-metadata', ['--project' => $this->project->id]);
        }

        session()->flash('success', 'Perubahan proyek berhasil disimpan.');
        $this->thumbnail = null; // reset thumbnail input
    }

    public function deleteProject()
    {
        if (in_array($this->project->status, ['under_review', 'approved'])) {
            session()->flash('error', 'Proyek yang sedang direview atau sudah lulus tidak dapat dihapus.');
            return;
        }

        if ($this->project->thumbnail_path) {
            Storage::disk('public')->delete($this->project->thumbnail_path);
        }

        $this->project->delete();
        session()->flash('success', 'Proyek berhasil dihapus.');
        return redirect()->route('dashboard');
    }



    // === Links Logic ===
    private function loadLinks()
    {
        $this->projectLinks = ProjectUrl::where('project_id', $this->project->id)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
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
            'project_id' => $this->project->id,
            'url_type' => $this->linkType,
            'url' => $this->linkUrl,
            'description' => $this->linkDescription,
            'is_public' => $this->linkIsPublic,
        ]);

        $this->linkUrl = '';
        $this->linkDescription = '';
        $this->loadLinks();
        $this->dispatch('link-added');
        session()->flash('link-success', 'Tautan berhasil ditambahkan.');
    }

    public function deleteLink($linkId)
    {
        $link = ProjectUrl::where('project_id', $this->project->id)->find($linkId);
        if ($link) {
            $link->delete();
            $this->loadLinks();
        }
    }

    // === Docs Logic ===
    private function loadDocs()
    {
        $this->projectDocs = Documentation::where('project_id', $this->project->id)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function uploadDocument()
    {
        $this->validate([
            'docFile' => 'required|file|max:10240|mimes:pdf,mp4,avi,mov,png,jpg,jpeg,webp,xlsx,pptx,docx',
            'docType' => 'required|in:pdf,video,image,spreadsheet,other',
            'docDescription' => 'nullable|string|max:255',
        ], [
            'docFile.required' => 'File wajib diunggah.',
            'docFile.max' => 'Ukuran file maksimal 10MB.',
            'docFile.mimes' => 'Format file tidak didukung.',
        ]);

        $file = $this->docFile;
        $path = $file->store('documentation/' . $this->project->id, 'local');

        Documentation::create([
            'project_id' => $this->project->id,
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
        $this->dispatch('doc-added');
        session()->flash('doc-success', 'Dokumen berhasil diunggah.');
    }

    public function deleteDocument($docId)
    {
        $doc = Documentation::where('project_id', $this->project->id)->find($docId);
        if ($doc) {
            $disk = Storage::disk('local')->exists($doc->file_path) ? 'local' : 'public';
            Storage::disk($disk)->delete($doc->file_path);
            $doc->delete();
            $this->loadDocs();
        }
    }

}; ?>

<div x-data="{ 
    showDeleteModal: false,
    showLinkModal: false,
    showDocModal: false
}"
@link-added.window="showLinkModal = false"
@doc-added.window="showDocModal = false">
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <a wire:navigate href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-700 mb-2 transition">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali ke Dashboard
                    </a>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Kelola Proyek: {{ $project->title }}</h2>
                    <div class="flex items-center gap-2 mt-2">
                        @if($project->status === 'submitted')
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Diajukan
                            </span>
                        @elseif($project->status === 'under_review')
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Sedang Direview
                            </span>
                        @elseif($project->status === 'approved')
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Lulus Validasi
                            </span>
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Disetujui
                            </span>
                        @else
                            <span class="flex items-center gap-1.5 text-sm font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Ditolak
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Kolom Kiri: Info & Danger Zone -->
            <div class="lg:col-span-2 space-y-8">


                <!-- Section: Info & Edit -->
                <div class="bg-white overflow-hidden shadow-sm ring-1 ring-slate-200 sm:rounded-2xl">
                    <div class="p-6 sm:p-8 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Informasi Proyek</h3>
                        <p class="text-sm text-slate-500 mt-1">Ubah detail utama dari proyek Anda.</p>
                    </div>
                    
                    <form wire:submit.prevent="submitProject">
                        <div class="p-6 sm:p-8 space-y-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Gambar Sampul Saat Ini</label>
                                @if($project->thumbnail_path)
                                    <img src="{{ Storage::url($project->thumbnail_path) }}" alt="{{ $project->title }}" class="h-32 object-cover rounded-xl border border-slate-200 shadow-sm">
                                @else
                                    <div class="h-32 w-48 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 border border-slate-200 shadow-sm">
                                        Tidak ada sampul
                                    </div>
                                @endif
                            </div>
                            @if(in_array($project->status, ['under_review', 'approved']))
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-200 text-sm text-slate-600 flex items-start gap-3">
                                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p>Proyek ini sedang dalam proses peninjauan atau telah lulus validasi. Anda hanya dapat mengubah <strong>Visibilitas Proyek</strong>. Jika ada kesalahan data, silakan hubungi guru yang bersangkutan.</p>
                            </div>
                            @endif

                            @if(!in_array($project->status, ['under_review', 'approved']))
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Gambar Sampul Proyek</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-400 transition bg-slate-50/50">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex justify-center text-sm text-slate-600 mt-2">
                                            <label for="thumbnail" class="relative cursor-pointer rounded-md bg-white font-medium text-blue-600 focus-within:outline-none hover:text-blue-500">
                                                <span>Unggah file baru</span>
                                                <input id="thumbnail" wire:model="thumbnail" type="file" class="sr-only" accept="image/*">
                                            </label>
                                            <p class="pl-1">atau tarik dan lepas</p>
                                        </div>
                                        <p class="text-xs text-slate-500">PNG, JPG, WebP maksimal 2MB</p>
                                    </div>
                                </div>
                                <div wire:loading wire:target="thumbnail" class="mt-2 text-blue-600 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs font-medium leading-none">Mengunggah gambar...</span>
                                </div>
                            </div>
                            @endif

                            <div>
                                <label for="title" class="block text-sm font-semibold text-slate-900 mb-1">Judul Proyek <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="title" id="title" {{ in_array($project->status, ['under_review', 'approved']) ? 'disabled' : '' }} class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-500">
                                @error('title') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-semibold text-slate-900 mb-1">Deskripsi Singkat <span class="text-red-500">*</span></label>
                                <textarea wire:model="description" id="description" rows="4" {{ in_array($project->status, ['under_review', 'approved']) ? 'disabled' : '' }} class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-500"></textarea>
                                @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="development_model" class="block text-sm font-semibold text-slate-900 mb-1">Model Pengembangan</label>
                                    <select wire:model="development_model" id="development_model" {{ in_array($project->status, ['under_review', 'approved']) ? 'disabled' : '' }} class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-500">
                                        <option value="waterfall">Waterfall</option>
                                        <option value="agile">Agile / Scrum</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    @error('development_model') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="github_url" class="block text-sm font-semibold text-slate-900 mb-1">URL GitHub <span class="text-red-500">*</span></label>
                                    <input type="url" wire:model="github_url" id="github_url" {{ in_array($project->status, ['under_review', 'approved']) ? 'disabled' : '' }} class="block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-500">
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
                            </div>
                        </div>

                        @if(!in_array($project->status, ['under_review']))
                        <div class="bg-slate-50 p-6 sm:px-8 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 rounded-b-2xl">
                            @if($project->status !== 'approved')
                            <button type="button" @click="showDeleteModal = true" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-200 hover:bg-red-50 sm:w-auto transition items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Hapus Proyek
                            </button>
                            @else
                            <div><!-- Placeholder to push save button to right --></div>
                            @endif
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:w-auto transition items-center">
                                <span wire:loading.remove wire:target="submitProject">Simpan Perubahan</span>
                                <span wire:loading.inline-flex wire:target="submitProject" class="items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                        @else
                        <div class="bg-amber-50 p-4 border-t border-amber-200 rounded-b-2xl text-center">
                            <p class="text-sm font-medium text-amber-800">Proyek sedang direview oleh guru dan tidak dapat diubah.</p>
                        </div>
                        @endif
                    </form>
                </div>
            </div> <!-- End of Kolom Kiri -->

            <!-- Kolom Kanan: Tautan & Dokumen -->
            <div class="space-y-8">
                <!-- Section: Links -->
                <div class="bg-white overflow-hidden shadow-sm ring-1 ring-slate-200 sm:rounded-2xl">
                    <div class="p-6 sm:p-8 border-b border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Kelola Tautan Proyek</h3>
                            <p class="text-sm text-slate-500 mt-1">Tambahkan link ke demo aplikasi, video, atau desain figma.</p>
                        </div>
                        @if($project->status !== 'under_review')
                        <button type="button" @click="showLinkModal = true" class="inline-flex flex-shrink-0 items-center justify-center rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">
                            + Tautan
                        </button>
                        @endif
                    </div>

                    <div class="p-6 sm:p-8">
                        @if (session()->has('link-success'))
                            <div class="mb-6 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-3">
                                <p class="text-sm font-medium">{{ session('link-success') }}</p>
                            </div>
                        @endif

                        <!-- Link List -->
                        <h4 class="text-sm font-bold text-slate-900 mb-4">Daftar Tautan Tersimpan</h4>
                        @if(count($projectLinks) > 0)
                            <div class="space-y-3">
                                @foreach($projectLinks as $link)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
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
                                            <svg class="w-5 h-5 {{ $linkIcons[$link['url_type']] ?? 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ $link['url'] }}" target="_blank" class="text-sm font-medium text-blue-600 hover:underline truncate block">{{ $link['description'] ?: $link['url'] }}</a>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ $linkLabels[$link['url_type']] ?? $link['url_type'] }}</span>
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-medium {{ $link['is_public'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $link['is_public'] ? 'Publik' : 'Privat' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button wire:click="deleteLink({{ $link['id'] }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition ml-2 flex-shrink-0" title="Hapus Link">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
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
                </div>

                <!-- Section: Docs -->
                <div class="bg-white overflow-hidden shadow-sm ring-1 ring-slate-200 sm:rounded-2xl">
                    <div class="p-6 sm:p-8 border-b border-slate-200 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Kelola Dokumen Proyek</h3>
                            <p class="text-sm text-slate-500 mt-1">Unggah file pendukung seperti modul PDF, video demo, atau presentasi.</p>
                        </div>
                        @if($project->status !== 'under_review')
                        <button type="button" @click="showDocModal = true" class="inline-flex flex-shrink-0 items-center justify-center rounded-xl bg-blue-50 px-3.5 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">
                            + Dokumen
                        </button>
                        @endif
                    </div>

                    <div class="p-6 sm:p-8">
                        @if (session()->has('doc-success'))
                            <div class="mb-6 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-3">
                                <p class="text-sm font-medium">{{ session('doc-success') }}</p>
                            </div>
                        @endif

                        <!-- Doc List -->
                        @if(count($projectDocs) > 0)
                            <div class="space-y-3">
                                @foreach($projectDocs as $doc)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 hover:bg-slate-50 transition">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('documentation.download', $doc['id']) }}" class="text-sm font-medium text-blue-600 hover:underline truncate block">
                                                {{ $doc['description'] ?: $doc['file_name'] }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ number_format($doc['file_size'] / 1024, 1) }} KB</span>
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[9px] font-medium {{ $doc['is_public'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $doc['is_public'] ? 'Publik' : 'Privat' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button wire:click="deleteDocument({{ $doc['id'] }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition ml-2 flex-shrink-0" title="Hapus Dokumen">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                <p class="text-sm font-medium text-slate-500">Belum ada dokumen yang diunggah.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div> <!-- End Kolom Kanan -->
        </div>
    </div>

    <!-- Modal Delete -->
    <div x-show="showDeleteModal" class="relative z-[60]" aria-labelledby="modal-delete-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showDeleteModal" x-transition.scale.origin.bottom class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md ring-1 ring-slate-200">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-delete-title">Hapus Proyek</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-slate-500">Apakah Anda yakin ingin menghapus proyek ini? Semua data terkait, termasuk dokumen, tautan, komentar dan penilaian dari guru, akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                        <button type="button" wire:click="deleteProject" class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto transition">
                            <span wire:loading.remove wire:target="deleteProject">Ya, Hapus Proyek</span>
                            <span wire:loading wire:target="deleteProject">Menghapus...</span>
                        </button>
                        <button type="button" @click="showDeleteModal = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add Link -->
    <div x-show="showLinkModal" class="relative z-[60]" aria-labelledby="modal-link-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="showLinkModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showLinkModal" x-transition.scale.origin.bottom class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl ring-1 ring-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white">
                        <h3 class="text-lg font-bold text-slate-900" id="modal-link-title">Tambah Tautan Proyek</h3>
                        <button type="button" @click="showLinkModal = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="bg-slate-50 p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Tautan</label>
                                <select wire:model="linkType" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="live_demo">Live Demo / Preview</option>
                                    <option value="video_tutorial">Video Demo / Tutorial</option>
                                    <option value="design">File Desain (Figma/Adobe)</option>
                                    <option value="documentation">Dokumentasi Online</option>
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
                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Pendek</label>
                            <input type="text" wire:model="linkDescription" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Misal: Link Aplikasi Android">
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">URL / Link <span class="text-rose-500">*</span></label>
                            <input type="url" wire:model="linkUrl" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="https://...">
                            @error('linkUrl') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-white px-6 py-4 rounded-b-2xl border-t border-slate-200 flex justify-end gap-3">
                        <button type="button" @click="showLinkModal = false" class="inline-flex justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                        <button wire:click="addLink" wire:loading.attr="disabled" wire:target="addLink" type="button" class="inline-flex justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="addLink">Tambah Tautan</span>
                            <span wire:loading wire:target="addLink">Menambahkan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add Doc -->
    <div x-show="showDocModal" class="relative z-[60]" aria-labelledby="modal-doc-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="showDocModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showDocModal" x-transition.scale.origin.bottom class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl ring-1 ring-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white">
                        <h3 class="text-lg font-bold text-slate-900" id="modal-doc-title">Unggah Dokumen Proyek</h3>
                        <button type="button" @click="showDocModal = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form wire:submit.prevent="uploadDocument">
                        <div class="bg-slate-50 p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe File</label>
                                    <select wire:model="docType" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="pdf">Dokumen PDF</option>
                                        <option value="video">Video (MP4/AVI/MOV)</option>
                                        <option value="image">Gambar Ekstra (JPG/PNG)</option>
                                        <option value="spreadsheet">Spreadsheet (XLSX)</option>
                                        <option value="other">File Lainnya (Termasuk PPTX/DOCX)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas</label>
                                    <select wire:model="docIsPublic" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        <option value="1">Publik</option>
                                        <option value="0">Privat (Hanya Guru)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Pendek</label>
                                <input type="text" wire:model="docDescription" class="block w-full rounded-lg border-slate-200 bg-white text-sm py-2 px-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Misal: Laporan Skripsi Bab 1">
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Unggah File <span class="text-rose-500">*</span></label>
                                <input type="file" wire:model="docFile" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-lg bg-white focus:outline-none">
                                <p class="mt-1.5 text-[10px] text-slate-500">Maks. 10MB.</p>
                                @error('docFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                
                                <div wire:loading wire:target="docFile" class="mt-2 inline-flex items-center gap-1.5 text-blue-600">
                                    <svg class="animate-spin h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span class="text-xs font-medium">Memproses file...</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white px-6 py-4 rounded-b-2xl border-t border-slate-200 flex justify-end gap-3">
                            <button type="button" @click="showDocModal = false" class="inline-flex justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">Batal</button>
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="uploadDocument">Unggah Dokumen</span>
                                <span wire:loading wire:target="uploadDocument">Mengunggah...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
