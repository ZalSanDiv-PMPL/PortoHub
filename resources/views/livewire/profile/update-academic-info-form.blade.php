<?php

use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $github_username = '';
    
    // Student fields
    public string $nis = '';
    public string $year = '';
    
    // Teacher fields
    public string $nip = '';
    public string $specialization = '';
    public string $department = '';

    public function mount()
    {
        $user = auth()->user();
        $token = $user->githubToken;
        $this->github_username = $token?->github_username ?? '';

        if ($user->role === 'student' && $user->student) {
            $this->nis = $user->student->nis ?? '';
            $this->year = $user->student->year ?? '';
        }

        if ($user->role === 'teacher' && $user->teacher) {
            $this->nip = $user->teacher->nip ?? '';
            $this->specialization = $user->teacher->specialization ?? '';
            $this->department = $user->teacher->department ?? '';
        }
    }

    public function saveAcademicInfo()
    {
        $user = auth()->user();

        if ($user->role === 'student' && $user->student) {
            // Check if already validated
            if ($user->student->is_validated) {
                $this->addError('general', 'Data tidak dapat diubah karena akun Anda telah divalidasi.');
                return;
            }

            $validated = $this->validate([
                'nis' => ['required', 'string', 'max:20', Rule::unique('students', 'nis')->ignore($user->student->id)],
                'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            ]);
            $user->student->fill($validated);
            $user->student->save();
        }

        if ($user->role === 'teacher' && $user->teacher) {
            $validated = $this->validate([
                'nip' => ['required', 'string', 'max:50', Rule::unique('teachers', 'nip')->ignore($user->teacher->id)],
                'specialization' => ['required', 'string', 'max:255'],
                'department' => ['required', 'string', 'max:255'],
            ]);
            $user->teacher->fill($validated);
            $user->teacher->save();
        }

        $this->dispatch('academic-info-updated');
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
        <h2 class="text-lg font-bold text-slate-900">Informasi Akademik</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola detail akademik dan lihat statistik performa Anda.</p>
    </header>

    <div class="mt-6">
        @error('general')
            <div class="mb-4 rounded-xl bg-rose-50 p-4 border border-rose-100 text-sm text-rose-700">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="saveAcademicInfo" class="space-y-6">
            {{-- Student Info --}}
            @if($role === 'student' && isset($student))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($student->is_validated)
                        <div class="sm:col-span-2 rounded-xl bg-amber-50 p-4 border border-amber-100 flex items-start gap-3">
                            <svg class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <p class="text-sm text-amber-800">Akun Anda telah divalidasi. Anda tidak dapat lagi mengubah NIS dan Tahun Angkatan. Silakan hubungi Admin jika terjadi kesalahan data.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">NIS</label>
                            <div class="mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed">{{ $student->nis }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Tahun Angkatan</label>
                            <div class="mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-500 cursor-not-allowed">{{ $student->year }}</div>
                        </div>
                    @else
                        <div>
                            <label for="nis" class="block text-sm font-semibold text-slate-700">NIS (Nomor Induk Siswa)</label>
                            <input wire:model="nis" id="nis" type="text" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" placeholder="Misal: 12345" />
                            <x-input-error :messages="$errors->get('nis')" class="mt-2" />
                        </div>
                        <div>
                            <label for="year" class="block text-sm font-semibold text-slate-700">Tahun Angkatan</label>
                            <input wire:model="year" id="year" type="number" min="2000" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" placeholder="Misal: {{ date('Y') }}" />
                            <x-input-error :messages="$errors->get('year')" class="mt-2" />
                        </div>
                    @endif

                    @if(isset($classAssignment))
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Kelas</label>
                        <div class="mt-1 px-3 py-2 bg-blue-50/50 border border-blue-100 rounded-xl text-sm font-medium text-blue-900">{{ $classAssignment->class }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Guru Pengampu</label>
                        <div class="mt-1 px-3 py-2 bg-blue-50/50 border border-blue-100 rounded-xl text-sm font-medium text-blue-900 flex items-center gap-2">
                            <x-avatar :url="$classAssignment->teacher->user->avatar_url ?? null" :name="$classAssignment->teacher->user->name ?? '-'" class="h-5 w-5 rounded-full" />
                            {{ $classAssignment->teacher->user->name ?? '-' }}
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Student Stats --}}
                @if(isset($projectStats))
                <div class="pt-6 border-t border-slate-100">
                    <label class="block text-sm font-bold text-slate-900 mb-3">Statistik Proyek</label>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-indigo-50/80 rounded-xl p-4 text-center border border-indigo-100 shadow-sm">
                            <div class="text-2xl font-black text-indigo-900">{{ $projectStats['total'] }}</div>
                            <div class="text-xs font-semibold text-indigo-600 mt-1 uppercase tracking-wider">Total</div>
                        </div>
                        <div class="bg-emerald-50/80 rounded-xl p-4 text-center border border-emerald-100 shadow-sm">
                            <div class="text-2xl font-black text-emerald-900">{{ $projectStats['approved'] }}</div>
                            <div class="text-xs font-semibold text-emerald-600 mt-1 uppercase tracking-wider">Lulus</div>
                        </div>
                        <div class="bg-amber-50/80 rounded-xl p-4 text-center border border-amber-100 shadow-sm">
                            <div class="text-2xl font-black text-amber-900">{{ number_format($projectStats['avgScore'], 1) }}</div>
                            <div class="text-xs font-semibold text-amber-600 mt-1 uppercase tracking-wider">Skor</div>
                        </div>
                    </div>
                </div>
                @endif
            @endif

            {{-- Teacher Info --}}
            @if($role === 'teacher' && isset($teacher))
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="nip" class="block text-sm font-semibold text-slate-700">NIP (Nomor Induk Pegawai)</label>
                        <input wire:model="nip" id="nip" type="text" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" placeholder="Masukkan NIP Anda" />
                        <x-input-error :messages="$errors->get('nip')" class="mt-2" />
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-semibold text-slate-700">Departemen</label>
                        <input wire:model="department" id="department" type="text" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" placeholder="Misal: Rekayasa Perangkat Lunak" />
                        <x-input-error :messages="$errors->get('department')" class="mt-2" />
                    </div>
                    <div>
                        <label for="specialization" class="block text-sm font-semibold text-slate-700">Spesialisasi</label>
                        <input wire:model="specialization" id="specialization" type="text" class="mt-1 block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm" placeholder="Misal: Web Development" />
                        <x-input-error :messages="$errors->get('specialization')" class="mt-2" />
                    </div>
                </div>

                @if(isset($teacherStats))
                <div class="pt-6 border-t border-slate-100">
                    <label class="block text-sm font-bold text-slate-900 mb-3">Statistik Kinerja</label>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50/80 rounded-xl p-4 text-center border border-blue-100 shadow-sm">
                            <div class="text-2xl font-black text-blue-900">{{ $teacherStats['totalClasses'] }}</div>
                            <div class="text-xs font-semibold text-blue-600 mt-1 uppercase tracking-wider">Kelas</div>
                        </div>
                        <div class="bg-indigo-50/80 rounded-xl p-4 text-center border border-indigo-100 shadow-sm">
                            <div class="text-2xl font-black text-indigo-900">{{ $teacherStats['totalStudents'] }}</div>
                            <div class="text-xs font-semibold text-indigo-600 mt-1 uppercase tracking-wider">Siswa</div>
                        </div>
                        <div class="bg-emerald-50/80 rounded-xl p-4 text-center border border-emerald-100 shadow-sm">
                            <div class="text-2xl font-black text-emerald-900">{{ $teacherStats['totalValidations'] }}</div>
                            <div class="text-xs font-semibold text-emerald-600 mt-1 uppercase tracking-wider">Review</div>
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

            {{-- Save Button (Only for unvalidated students or teachers) --}}
            @if(($role === 'student' && !$student->is_validated) || $role === 'teacher')
                <div class="flex items-center gap-4 pt-4 border-t border-slate-100">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                        <span wire:loading.remove wire:target="saveAcademicInfo">{{ __('Simpan Data Akademik') }}</span>
                        <span wire:loading.inline-flex wire:target="saveAcademicInfo" class="items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>

                    <x-action-message class="me-3 text-sm font-medium text-emerald-600" on="academic-info-updated">
                        {{ __('Berhasil disimpan.') }}
                    </x-action-message>
                </div>
            @endif
        </form>
    </div>
</section>
