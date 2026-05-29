<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $github_username = '';

    public function mount()
    {
        $user = auth()->user();
        $token = $user->githubToken;
        $this->github_username = $token?->github_username ?? '';
    }

    public function with()
    {
        $user = auth()->user();
        $role = $user->role;

        $data = ['role' => $role, 'user' => $user];

        if ($role === 'student' && $user->student) {
            $student = $user->student;
            $data['student'] = $student;
            $data['classAssignment'] = $student->classAssignments()->with('teacher.user')->first();
            $data['projectStats'] = [
                'total' => $student->projects()->count(),
                'approved' => $student->projects()->where('status', 'approved')->count(),
                'avgScore' => $student->projects()
                    ->whereHas('validation')
                    ->with('validation')
                    ->get()
                    ->map(fn ($p) => $p->validation ? ($p->validation->functionality_score + $p->validation->code_quality_score + $p->validation->documentation_score + $p->validation->originality_score) / 4 : 0)
                    ->avg() ?? 0,
            ];
        }

        if ($role === 'teacher' && $user->teacher) {
            $teacher = $user->teacher;
            $data['teacher'] = $teacher;
            $data['teacherStats'] = [
                'totalStudents' => $teacher->students()->count(),
                'totalValidations' => \App\Models\Validation::where('teacher_id', $teacher->id)->count(),
                'totalClasses' => $teacher->classAssignments()->distinct('class')->count(),
            ];
        }

        return $data;
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Informasi Akademik</h2>
        <p class="mt-1 text-sm text-gray-600">Detail akademik dan statistik proyek Anda.</p>
    </header>

    <div class="mt-6 space-y-6">

        {{-- Student Info --}}
        @if($role === 'student' && isset($student))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">NIS</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $student->nis }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tahun Angkatan</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $student->year }}</div>
            </div>
            @if(isset($classAssignment))
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $classAssignment->class }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Guru Pengampu</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $classAssignment->teacher->user->name ?? '-' }}</div>
            </div>
            @endif
        </div>

        {{-- Student Stats --}}
        @if(isset($projectStats))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statistik Proyek</label>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 rounded-xl p-3 text-center border border-blue-100">
                    <div class="text-xl font-bold text-blue-900">{{ $projectStats['total'] }}</div>
                    <div class="text-xs text-blue-600 mt-0.5">Total Proyek</div>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 text-center border border-emerald-100">
                    <div class="text-xl font-bold text-emerald-900">{{ $projectStats['approved'] }}</div>
                    <div class="text-xs text-emerald-600 mt-0.5">Disetujui</div>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center border border-amber-100">
                    <div class="text-xl font-bold text-amber-900">{{ number_format($projectStats['avgScore'], 1) }}</div>
                    <div class="text-xs text-amber-600 mt-0.5">Rata-rata Skor</div>
                </div>
            </div>
        </div>
        @endif

        {{-- GitHub Username --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">GitHub Username</label>
            <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-2 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                {{ $github_username ?: 'Belum terhubung' }}
            </div>
        </div>
        @endif

        {{-- Teacher Info --}}
        @if($role === 'teacher' && isset($teacher))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">NIP</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $teacher->nip }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Spesialisasi</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $teacher->specialization }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Departemen</label>
                <div class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">{{ $teacher->department }}</div>
            </div>
        </div>

        @if(isset($teacherStats))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statistik</label>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-indigo-50 rounded-xl p-3 text-center border border-indigo-100">
                    <div class="text-xl font-bold text-indigo-900">{{ $teacherStats['totalStudents'] }}</div>
                    <div class="text-xs text-indigo-600 mt-0.5">Siswa Diampu</div>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 text-center border border-emerald-100">
                    <div class="text-xl font-bold text-emerald-900">{{ $teacherStats['totalValidations'] }}</div>
                    <div class="text-xs text-emerald-600 mt-0.5">Proyek Divalidasi</div>
                </div>
                <div class="bg-blue-50 rounded-xl p-3 text-center border border-blue-100">
                    <div class="text-xl font-bold text-blue-900">{{ $teacherStats['totalClasses'] }}</div>
                    <div class="text-xs text-blue-600 mt-0.5">Total Kelas</div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- Admin Info --}}
        @if($role === 'admin')
        <div class="rounded-xl bg-blue-50 p-4 border border-blue-100 text-blue-700 text-sm">
            Anda login sebagai <span class="font-semibold">Administrator</span>. Kelola platform melalui Dashboard Admin.
        </div>
        @endif

    </div>
</section>
