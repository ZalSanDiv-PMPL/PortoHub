<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Project;
use App\Models\ClassAssignment;
use Livewire\Attributes\Validate;

new class extends Component {
    public string $currentTab = 'overview';

    // Form inputs for Class Assignment (reused for both standalone and modal)
    #[Validate('required|exists:teachers,id')]
    public string $selectedTeacherId = '';

    #[Validate('required|exists:students,id')]
    public string $selectedStudentId = '';

    #[Validate('required|string|min:3')]
    public string $className = '';

    #[Validate('required|integer|in:1,2')]
    public string $semester = '1';

    // Modal state for student validation
    public bool $isValidateStudentModalOpen = false;
    public ?int $studentToValidateId = null;
    public ?Student $studentToValidate = null;

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function with()
    {
        return [
            'totalUsers' => User::count(),
            'totalTeachers' => Teacher::where('is_validated', true)->count(),
            'totalStudents' => Student::where('is_validated', true)->count(),
            'totalProjects' => Project::count(),
            
            'pendingTeachers' => Teacher::with('user')->where('is_validated', false)->get(),
            'pendingStudents' => Student::with('user')->where('is_validated', false)->get(),
            
            'allTeachers' => Teacher::with('user')->where('is_validated', true)->get(),
            'allStudents' => Student::with('user')->where('is_validated', true)->get(),
            
            'classAssignments' => ClassAssignment::with(['teacher.user', 'student.user'])->orderBy('created_at', 'desc')->get(),
        ];
    }

    public function switchTab($tab)
    {
        $this->currentTab = $tab;
    }

    public function validateTeacher($id)
    {
        $teacher = Teacher::find($id);
        if ($teacher) {
            $teacher->update(['is_validated' => true]);
            session()->flash('success', 'Guru berhasil divalidasi.');
        }
    }

    public function openValidateStudentModal($id)
    {
        $this->studentToValidateId = $id;
        $this->studentToValidate = Student::with('user')->find($id);
        $this->isValidateStudentModalOpen = true;
        
        $this->reset(['selectedTeacherId', 'className']);
        $this->semester = '1';
    }

    public function closeValidateStudentModal()
    {
        $this->isValidateStudentModalOpen = false;
        $this->reset(['studentToValidateId', 'studentToValidate', 'selectedTeacherId', 'className']);
        $this->semester = '1';
        $this->resetValidation();
    }

    public function confirmValidationAndAssign()
    {
        $this->validate([
            'selectedTeacherId' => 'required|exists:teachers,id',
            'className' => 'required|string|min:3',
            'semester' => 'required|integer|in:1,2',
        ]);

        if (!$this->studentToValidate) {
            return;
        }

        // 1. Validate the student
        $this->studentToValidate->update(['is_validated' => true]);

        // 2. Assign to class
        $exists = ClassAssignment::where('teacher_id', $this->selectedTeacherId)
            ->where('student_id', $this->studentToValidateId)
            ->where('class', $this->className)
            ->where('semester', $this->semester)
            ->exists();

        if (!$exists) {
            ClassAssignment::create([
                'teacher_id' => $this->selectedTeacherId,
                'student_id' => $this->studentToValidateId,
                'class' => $this->className,
                'semester' => $this->semester,
                'is_active' => true,
            ]);
        }

        $this->closeValidateStudentModal();
        session()->flash('success', 'Siswa berhasil divalidasi dan ditempatkan ke kelas.');
    }

    public function assignClass()
    {
        $this->validate();

        // Check if assignment already exists
        $exists = ClassAssignment::where('teacher_id', $this->selectedTeacherId)
            ->where('student_id', $this->selectedStudentId)
            ->where('class', $this->className)
            ->where('semester', $this->semester)
            ->exists();

        if ($exists) {
            session()->flash('error', 'Penempatan kelas ini sudah ada.');
            return;
        }

        ClassAssignment::create([
            'teacher_id' => $this->selectedTeacherId,
            'student_id' => $this->selectedStudentId,
            'class' => $this->className,
            'semester' => $this->semester,
            'is_active' => true,
        ]);

        $this->reset(['selectedTeacherId', 'selectedStudentId', 'className', 'semester']);
        $this->semester = '1';
        
        session()->flash('success', 'Penempatan kelas berhasil ditambahkan.');
    }

    public function deleteAssignment($id)
    {
        $assignment = ClassAssignment::find($id);
        if ($assignment) {
            $assignment->delete();
            session()->flash('success', 'Penempatan kelas berhasil dihapus.');
        }
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <!-- Header Admin -->
    <x-glass-card class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-6">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard Admin</h2>
            <p class="mt-2 text-slate-600">Selamat datang, {{ explode(' ', auth()->user()->name)[0] }}. Kelola platform, validasi pengguna, dan penempatan kelas.</p>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="flex p-1 space-x-1 bg-slate-100/80 backdrop-blur-md rounded-xl">
            <button wire:click="switchTab('overview')" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $currentTab === 'overview' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Ikhtisar</button>
            <button wire:click="switchTab('users')" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $currentTab === 'users' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Manajemen Pengguna</button>
            <button wire:click="switchTab('classes')" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $currentTab === 'classes' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Penempatan Kelas</button>
        </div>
    </x-glass-card>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50/80 backdrop-blur-md border border-emerald-100 text-emerald-700 flex items-center">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50/80 backdrop-blur-md border border-rose-100 text-rose-700 flex items-center">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- TAB 1: OVERVIEW -->
    @if($currentTab === 'overview')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Stat: Total Users -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-200/50">
                    <svg class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Total Akun</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalUsers }}</p>
            </dd>
        </div>
        <!-- Stat: Total Teachers -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-indigo-50/80 p-3 ring-1 ring-indigo-100/50">
                    <svg class="h-6 w-6 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M12 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Guru Aktif</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalTeachers }}</p>
            </dd>
        </div>
        <!-- Stat: Total Students -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-emerald-50/80 p-3 ring-1 ring-emerald-100/50">
                    <svg class="h-6 w-6 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M12 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Siswa Aktif</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalStudents }}</p>
            </dd>
        </div>
        <!-- Stat: Total Projects -->
        <div class="relative overflow-hidden rounded-2xl bg-white/70 backdrop-blur-xl p-6 shadow-sm ring-1 ring-slate-200/50 border border-white/50 transition hover:shadow-md hover:bg-white/90">
            <dt>
                <div class="absolute rounded-xl bg-blue-50/80 p-3 ring-1 ring-blue-100/50">
                    <svg class="h-6 w-6 text-blue-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                </div>
                <p class="ml-16 truncate text-sm font-medium text-slate-500">Proyek Terdaftar</p>
            </dt>
            <dd class="ml-16 flex items-baseline">
                <p class="text-2xl font-bold text-slate-900">{{ $totalProjects }}</p>
            </dd>
        </div>
    </div>
    @endif

    <!-- TAB 2: MANAJEMEN PENGGUNA -->
    @if($currentTab === 'users')
    <div class="space-y-8">
        <!-- Tabel Guru Menunggu Validasi -->
        <div>
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center">
                Guru Menunggu Validasi
                @if($pendingTeachers->count() > 0)
                <span class="ml-2 inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">{{ $pendingTeachers->count() }}</span>
                @endif
            </h3>
            <x-glass-card>
                <div class="min-w-full overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200/60 text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 backdrop-blur-md">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-900">Nama Guru</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">NIP</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">Spesialisasi</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 bg-transparent">
                            @forelse($pendingTeachers as $teacher)
                                <tr class="transition hover:bg-white/60">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $teacher->user->name }}<br><span class="text-xs text-slate-500 font-normal">{{ $teacher->user->email }}</span></td>
                                    <td class="px-6 py-4 text-slate-600">{{ $teacher->nip }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $teacher->specialization }} ({{ $teacher->department }})</td>
                                    <td class="px-6 py-4 text-right">
                                        <button wire:click="validateTeacher({{ $teacher->id }})" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">Validasi</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Semua guru sudah tervalidasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-glass-card>
        </div>

        <!-- Tabel Siswa Menunggu Validasi -->
        <div>
            <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center">
                Siswa Menunggu Validasi
                @if($pendingStudents->count() > 0)
                <span class="ml-2 inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">{{ $pendingStudents->count() }}</span>
                @endif
            </h3>
            <x-glass-card>
                <div class="min-w-full overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200/60 text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 backdrop-blur-md">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-900">Nama Siswa</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">NIS</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">Angkatan</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 bg-transparent">
                            @forelse($pendingStudents as $student)
                                <tr class="transition hover:bg-white/60">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $student->user->name }}<br><span class="text-xs text-slate-500 font-normal">{{ $student->user->email }}</span></td>
                                    <td class="px-6 py-4 text-slate-600">
                                        @if(empty($student->nis))
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Belum isi NIS</span>
                                        @else
                                            {{ $student->nis }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $student->year ?? '-' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        @if(empty($student->nis))
                                            <button disabled class="inline-flex items-center justify-center rounded-lg bg-slate-300 px-3 py-1.5 text-sm font-semibold text-white shadow-sm cursor-not-allowed" title="Siswa harus melengkapi NIS terlebih dahulu">Validasi & Tempatkan</button>
                                        @else
                                            <button wire:click="openValidateStudentModal({{ $student->id }})" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Validasi & Tempatkan</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Semua siswa sudah tervalidasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-glass-card>
        </div>
    </div>
    @endif

    <!-- TAB 3: CLASS ASSIGNMENTS -->
    @if($currentTab === 'classes')
    <div class="space-y-8">
        
        <!-- Formulir Penempatan -->
        <x-glass-card class="p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Buat Penempatan Kelas Baru</h3>
            <form wire:submit="assignClass" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                
                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Guru</label>
                    <select wire:model="selectedTeacherId" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Guru...</option>
                        @foreach($allTeachers as $t)
                            <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedTeacherId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Siswa</label>
                    <select wire:model="selectedStudentId" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Siswa...</option>
                        @foreach($allStudents as $s)
                            <option value="{{ $s->id }}">{{ $s->user->name }} ({{ $s->nis }})</option>
                        @endforeach
                    </select>
                    @error('selectedStudentId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Nama Kelas</label>
                    <input type="text" wire:model="className" placeholder="Misal: X RPL A" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('className') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-sm font-semibold text-slate-900 mb-1">Semester</label>
                    <select wire:model="semester" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                    @error('semester') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-1">
                    <button type="submit" class="w-full inline-flex justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Tambah Relasi
                    </button>
                </div>
            </form>
        </x-glass-card>

        <!-- Tabel Penempatan -->
        <div>
            <h3 class="text-lg font-bold text-slate-900 mb-4">Daftar Penempatan Kelas Aktif</h3>
            <x-glass-card>
                <div class="min-w-full overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200/60 text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50/50 backdrop-blur-md">
                            <tr>
                                <th class="px-6 py-4 font-semibold text-slate-900">Nama Kelas</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">Semester</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">Guru Pengampu</th>
                                <th class="px-6 py-4 font-semibold text-slate-900">Siswa</th>
                                <th class="px-6 py-4 font-semibold text-slate-900 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 bg-transparent">
                            @forelse($classAssignments as $assignment)
                                <tr class="transition hover:bg-white/60">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $assignment->class }}</td>
                                    <td class="px-6 py-4 text-slate-600">Semester {{ $assignment->semester }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $assignment->teacher->user->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $assignment->student->user->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button x-on:click.prevent="if(confirm('Yakin ingin menghapus penempatan kelas ini?')) { $wire.deleteAssignment({{ $assignment->id }}) }" class="text-rose-600 hover:text-rose-800 font-semibold bg-rose-50 px-3 py-1.5 rounded-lg transition hover:bg-rose-100 text-xs">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada penempatan kelas yang dibuat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-glass-card>
        </div>

    </div>
    @endif

    <!-- MODAL: VALIDASI & ASSIGNMENT SISWA -->
    @if($isValidateStudentModalOpen && $studentToValidate)
    <style>
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleUp { from { opacity: 0; transform: translateY(10px) scale(0.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
        .animate-scale-up { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
    <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity animate-fade-in"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg ring-1 ring-slate-200 animate-scale-up">
                    
                    <form wire:submit="confirmValidationAndAssign">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-title">Validasi & Penempatan</h3>
                                    <p class="mt-1 text-sm text-slate-500">Siswa: <span class="font-semibold text-slate-800">{{ $studentToValidate->user->name }}</span> ({{ $studentToValidate->nis }})</p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <p class="text-sm text-slate-600 mb-4 bg-blue-50/50 p-3 rounded-lg border border-blue-100">Dengan menekan tombol validasi, akun siswa ini akan diaktifkan dan langsung dipetakan ke dalam kelas berikut.</p>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-1">Guru Pengampu</label>
                                    <select wire:model="selectedTeacherId" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Pilih Guru...</option>
                                        @foreach($allTeachers as $t)
                                            <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedTeacherId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-1">Nama Kelas</label>
                                    <input type="text" wire:model="className" placeholder="Misal: X RPL A" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('className') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-900 mb-1">Semester</label>
                                    <select wire:model="semester" class="block w-full rounded-xl border-slate-300 bg-white/50 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="1">Semester 1 (Ganjil)</option>
                                        <option value="2">Semester 2 (Genap)</option>
                                    </select>
                                    @error('semester') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 rounded-b-2xl border-t border-slate-200">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:ml-3 sm:w-auto transition">
                                Validasi & Simpan
                            </button>
                            <button type="button" wire:click="closeValidateStudentModal" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
