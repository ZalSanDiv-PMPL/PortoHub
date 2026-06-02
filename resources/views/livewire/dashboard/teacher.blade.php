<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Project;

new class extends Component {
    use WithPagination;

    public string $classFilter = '';
    public string $statusFilter = 'menunggu'; // menunggu, revisi, lulus, semua

    public function updatingClassFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function with()
    {
        $teacher = auth()->user()->teacher;
        
        if (!$teacher) {
            return [
                'siswaDiampu' => 0,
                'menungguValidasi' => 0,
                'totalKelas' => 0,
                'daftarKelas' => collect(),
                'antreanProyek' => collect() // or empty paginator if needed
            ];
        }

        $siswaDiampu = $teacher->students()->count();
        $daftarKelas = $teacher->classAssignments()->distinct('class')->pluck('class');
        $totalKelas = $daftarKelas->count();
        
        $studentIds = $teacher->students()->pluck('students.id');

        // Menghitung yang benar-benar menunggu validasi untuk stat card
        $menungguValidasi = Project::whereIn('student_id', $studentIds)
            ->whereIn('status', ['submitted', 'under_review'])
            ->count();

        // Filter berdasarkan kelas jika dipilih
        if (!empty($this->classFilter)) {
            $filteredStudentIds = $teacher->classAssignments()
                ->where('class', $this->classFilter)
                ->pluck('student_id');
            $studentIds = $studentIds->intersect($filteredStudentIds);
        }
        
        $query = Project::whereIn('student_id', $studentIds)
            ->with(['student.user', 'student.classAssignments' => function($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            }])
            ->withCount(['comments as unread_comments_count' => function($q) {
                // Comments from students that haven't been viewed/replied
                $q->whereNotNull('student_id')->where('status', 'pending');
            }]);

        // Filter berdasarkan status
        if ($this->statusFilter === 'menunggu') {
            $query->whereIn('status', ['submitted', 'under_review'])
                  ->orderByRaw("FIELD(status, 'submitted', 'under_review')");
        } elseif ($this->statusFilter === 'revisi') {
            $query->where('status', 'rejected');
        } elseif ($this->statusFilter === 'lulus') {
            $query->where('status', 'approved');
        }

        $antreanProyek = $query->orderBy('submission_date', 'asc')->paginate(10);

        return [
            'siswaDiampu' => $siswaDiampu,
            'menungguValidasi' => $menungguValidasi,
            'totalKelas' => $totalKelas,
            'daftarKelas' => $daftarKelas,
            'antreanProyek' => $antreanProyek,
        ];
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
            <select wire:model.live="classFilter" class="rounded-xl border-slate-200 bg-white/80 backdrop-blur-md px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 transition hover:bg-white">
                <option value="">Semua Kelas</option>
                @foreach($daftarKelas as $kelas)
                    <option value="{{ $kelas }}">{{ $kelas }}</option>
                @endforeach
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
    <div class="mb-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h3 class="text-lg font-bold text-slate-900">Antrean Validasi Proyek</h3>
        </div>
        
        <!-- Tab Filters -->
        <div class="flex space-x-2 border-b border-slate-200">
            <button wire:click="$set('statusFilter', 'menunggu')" class="px-4 py-2 text-sm font-medium border-b-2 {{ $statusFilter === 'menunggu' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} transition">Menunggu Review</button>
            <button wire:click="$set('statusFilter', 'revisi')" class="px-4 py-2 text-sm font-medium border-b-2 {{ $statusFilter === 'revisi' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} transition">Revisi Siswa</button>
            <button wire:click="$set('statusFilter', 'lulus')" class="px-4 py-2 text-sm font-medium border-b-2 {{ $statusFilter === 'lulus' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} transition">Selesai/Lulus</button>
            <button wire:click="$set('statusFilter', 'semua')" class="px-4 py-2 text-sm font-medium border-b-2 {{ $statusFilter === 'semua' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} transition">Semua</button>
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
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold overflow-hidden">
                                            <x-avatar :url="$proyek->student->user->avatar_url" :name="$proyek->student->user->name" />
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-slate-900">{{ $proyek->student->user->name }}</div>
                                        <div class="text-slate-500 text-xs">{{ $proyek->student->nis }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                {{ $proyek->student->active_class }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-900 font-medium">{{ $proyek->title }}</div>
                                <div class="text-xs text-slate-500">
                                    @if($proyek->status === 'rejected')
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                            Menunggu Revisi Siswa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                            {{ ucfirst(str_replace('_', ' ', $proyek->status)) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                {{ $proyek->submission_date ? \Carbon\Carbon::parse($proyek->submission_date)->diffForHumans() : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium relative">
                                <a wire:navigate href="{{ route('teacher.review', $proyek->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-900 font-semibold bg-blue-50 px-3 py-1.5 rounded-lg transition hover:bg-blue-100 relative">
                                    Review
                                    @if($proyek->unread_comments_count > 0)
                                        <span class="absolute -top-1 -right-1 flex h-3 w-3" title="{{ $proyek->unread_comments_count }} balasan baru">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                        </span>
                                    @endif
                                </a>
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

    <div class="mt-4">
        {{ $antreanProyek->links() }}
    </div>
</div>
