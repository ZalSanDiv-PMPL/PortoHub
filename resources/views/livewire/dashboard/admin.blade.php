<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Project;
use App\Models\ClassAssignment;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithPagination;
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

    public string $searchClass = '';

    public function updatingSearchClass()
    {
        $this->resetPage('caPage');
    }

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
        $data = [];
        $data['allTeachers'] = Teacher::with('user')->where('is_validated', true)->get();
        
        if ($this->currentTab === 'overview') {
            $data['totalUsers'] = User::count();
            $data['totalTeachers'] = Teacher::where('is_validated', true)->count();
            $data['totalStudents'] = Student::where('is_validated', true)->count();
            $data['totalProjects'] = Project::count();
            $data['recentPendingStudents'] = Student::with('user')->where('is_validated', false)->latest()->take(3)->get();
        } else {
            $data['totalUsers'] = 0;
            $data['totalTeachers'] = 0;
            $data['totalStudents'] = 0;
            $data['totalProjects'] = 0;
            $data['recentPendingStudents'] = collect([]);
        }

        if ($this->currentTab === 'users') {
            $data['pendingTeachers'] = Teacher::with('user')->where('is_validated', false)->paginate(10, ['*'], 'ptPage');
            $data['pendingStudents'] = Student::with('user')->where('is_validated', false)->paginate(10, ['*'], 'psPage');
        } else {
            $data['pendingTeachers'] = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            $data['pendingStudents'] = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        if ($this->currentTab === 'classes') {
            $data['allStudents'] = Student::with('user')->where('is_validated', true)->get();
            
            $query = ClassAssignment::with(['teacher.user', 'student.user']);
            
            if (!empty($this->searchClass)) {
                $query->where(function($q) {
                    $q->where('class', 'like', '%' . $this->searchClass . '%')
                      ->orWhere('semester', 'like', '%' . $this->searchClass . '%')
                      ->orWhereHas('student.user', function($q2) {
                          $q2->where('name', 'like', '%' . $this->searchClass . '%');
                      })
                      ->orWhereHas('teacher.user', function($q2) {
                          $q2->where('name', 'like', '%' . $this->searchClass . '%');
                      });
                });
            }
            
            $data['classAssignments'] = $query->orderBy('created_at', 'desc')->paginate(10, ['*'], 'caPage');
        } else {
            $data['allStudents'] = collect([]);
            $data['classAssignments'] = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        return $data;
    }

    public function switchTab($tab)
    {
        $this->currentTab = $tab;
        $this->resetPage('ptPage');
        $this->resetPage('psPage');
        $this->resetPage('caPage');
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

        if (empty($this->studentToValidate->nis) || empty($this->studentToValidate->year)) {
            session()->flash('error', 'Siswa tidak memiliki NIS atau Tahun Angkatan. Validasi gagal.');
            return;
        }

        // 1. Validate the student
        $this->studentToValidate->update(['is_validated' => true]);

        // 2. Assign to class
        $exists = ClassAssignment::where('student_id', $this->studentToValidateId)
            ->where('semester', $this->semester)
            ->exists();

        if ($exists) {
            $this->closeValidateStudentModal();
            session()->flash('error', 'Siswa berhasil divalidasi, tetapi gagal dimasukkan kelas karena sudah memiliki kelas aktif di semester ini.');
            return;
        }
            ClassAssignment::create([
                'teacher_id' => $this->selectedTeacherId,
                'student_id' => $this->studentToValidateId,
                'class' => $this->className,
                'semester' => $this->semester,
                'is_active' => true,
            ]);

        $this->closeValidateStudentModal();
        session()->flash('success', 'Siswa berhasil divalidasi dan ditempatkan ke kelas.');
    }

    public function assignClass()
    {
        $this->validate();

        // Check if assignment already exists
        $exists = ClassAssignment::where('student_id', $this->selectedStudentId)
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

<div class="flex h-[calc(100vh-65px)] overflow-hidden bg-slate-50/50" x-data="{ sidebarOpen: false }">
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="absolute inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transition-transform duration-300 md:relative md:translate-x-0 shadow-sm flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 md:hidden">
            <span class="text-lg font-bold text-slate-900">Menu Admin</span>
            <button @click="sidebarOpen = false" class="text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-4 flex-1 space-y-1 overflow-y-auto">
            <button wire:click="switchTab('overview')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $currentTab === 'overview' ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ $currentTab === 'overview' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                Overview
            </button>
            <button wire:click="switchTab('users')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $currentTab === 'users' ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ $currentTab === 'users' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                Manajemen Pengguna
                @if($pendingTeachers->total() + $pendingStudents->total() > 0)
                <span class="ml-auto bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $pendingTeachers->total() + $pendingStudents->total() }}</span>
                @endif
            </button>
            <button wire:click="switchTab('classes')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ $currentTab === 'classes' ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <svg class="w-5 h-5 {{ $currentTab === 'classes' ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                Penempatan Kelas
            </button>
        </div>
    </aside>

    <!-- Overlay Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 md:hidden transition-opacity" style="display: none;"></div>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto w-full relative">
        <!-- Header Sticky -->
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="text-slate-500 hover:text-slate-900 md:hidden">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </button>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900 leading-tight">
                        @if($currentTab === 'overview') Overview Admin
                        @elseif($currentTab === 'users') Manajemen Pengguna
                        @elseif($currentTab === 'classes') Penempatan Kelas
                        @endif
                    </h2>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 md:space-y-8">
            <!-- Alert Messages -->
            <x-action-message on="success" class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl p-4 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </x-action-message>
            @if(session('error'))
            <div class="bg-rose-50 text-rose-800 border border-rose-200 rounded-xl p-4 flex items-center shadow-sm mb-6">
                <svg class="w-5 h-5 mr-2 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ session('error') }}
            </div>
            @endif

            <!-- TAB 1: OVERVIEW -->
            @if($currentTab === 'overview')
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
                
                <!-- Total Users Bento Box -->
                <div :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-3xl p-6 text-white shadow-lg shadow-indigo-200/50 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 opacity-20 group-hover:scale-110 group-hover:opacity-30 transition-all duration-500">
                        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    </div>
                    <div class="relative z-10">
                        <p class="text-indigo-100 font-medium text-sm">Total Akun Terdaftar</p>
                        <h3 class="text-4xl font-black mt-2">{{ $totalUsers }}</h3>
                    </div>
                </div>

                <!-- Total Teachers Bento Box -->
                <div :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out delay-75 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md relative overflow-hidden group">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-sky-50 flex items-center justify-center text-sky-600 group-hover:bg-sky-500 group-hover:text-white transition-colors duration-300">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M12 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                        </div>
                        <div>
                            <p class="text-slate-500 font-medium text-sm">Guru Aktif</p>
                            <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $totalTeachers }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Total Students Bento Box -->
                <div :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out delay-150 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md relative overflow-hidden group">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </div>
                        <div>
                            <p class="text-slate-500 font-medium text-sm">Siswa Aktif</p>
                            <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $totalStudents }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Total Projects Bento Box -->
                <div :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out delay-200 bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-md relative overflow-hidden group">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                        </div>
                        <div>
                            <p class="text-slate-500 font-medium text-sm">Total Proyek</p>
                            <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $totalProjects }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Pending Students Section -->
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out mt-8">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-rose-50 text-rose-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Siswa Perlu Validasi</h3>
                                <p class="text-xs text-slate-500">Daftar siswa terbaru yang menunggu tinjauan admin.</p>
                            </div>
                        </div>
                        <button wire:click="switchTab('users')" class="text-sm font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-4 py-2 rounded-xl transition-colors shrink-0">
                            Selengkapnya &rarr;
                        </button>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentPendingStudents as $student)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-slate-200 overflow-hidden shrink-0 ring-2 ring-white shadow-sm">
                                    <x-avatar :url="$student->user->avatar_url" :name="$student->user->name" />
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $student->user->name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <p class="text-xs text-slate-500">{{ $student->user->email }}</p>
                                        @if(empty($student->nis))
                                            <span class="inline-flex items-center rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">Data NIS Kosong</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ $student->nis }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(empty($student->nis) || empty($student->year))
                                <button disabled class="text-sm font-bold text-slate-400 bg-slate-100 cursor-not-allowed shadow-sm px-4 py-1.5 rounded-lg transition-colors sm:w-auto w-full">Data Tidak Lengkap</button>
                            @else
                                <button wire:click="openValidateStudentModal({{ $student->id }})" class="text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm px-4 py-1.5 rounded-lg transition-colors sm:w-auto w-full">Validasi</button>
                            @endif
                        </div>
                        @empty
                        <div class="p-8 text-center">
                            <svg class="w-10 h-10 mx-auto text-emerald-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-slate-500 font-medium text-sm">Hebat! Tidak ada siswa yang menunggu validasi.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            <!-- TAB 2: MANAJEMEN PENGGUNA -->
            @if($currentTab === 'users')
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="space-y-6 md:space-y-8 transition-all duration-500 ease-out">
                <!-- Tabel Guru -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-5 md:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                                Guru Menunggu Validasi
                            </h3>
                            <p class="text-sm text-slate-500 mt-1">Daftar guru yang baru mendaftar dan membutuhkan persetujuan.</p>
                        </div>
                        @if($pendingTeachers->total() > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-rose-50 px-3 py-1 text-sm font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20 whitespace-nowrap">{{ $pendingTeachers->total() }} Menunggu</span>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Nama Guru</th>
                                    <th class="px-6 py-4">NIP</th>
                                    <th class="px-6 py-4">Spesialisasi</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($pendingTeachers as $teacher)
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden shrink-0"><x-avatar :url="$teacher->user->avatar_url" :name="$teacher->user->name" /></div>
                                                <div>
                                                    <p class="font-bold text-slate-900">{{ $teacher->user->name }}</p>
                                                    <p class="text-xs text-slate-500">{{ $teacher->user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if(empty($teacher->nip))
                                                <span class="text-rose-500 font-medium italic text-xs">Menunggu Guru</span>
                                            @else
                                                <span class="font-mono text-slate-700 font-medium">{{ $teacher->nip }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if(empty($teacher->specialization))
                                                <span class="inline-flex items-center rounded-md bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">Data Kosong</span>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">{{ $teacher->specialization }}</span>
                                                <span class="text-xs text-slate-500 ml-1">({{ $teacher->department }})</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if(empty($teacher->nip) || empty($teacher->specialization) || empty($teacher->department))
                                                <button disabled class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-400 cursor-not-allowed">Setujui</button>
                                            @else
                                                <button wire:click="validateTeacher({{ $teacher->id }})" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 opacity-80 group-hover:opacity-100">Setujui</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500"><svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Tidak ada antrean validasi guru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($pendingTeachers->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $pendingTeachers->links() }}
                    </div>
                    @endif
                </div>

                <!-- Tabel Siswa -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-5 md:p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814M12 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
                                Siswa Menunggu Validasi
                            </h3>
                            <p class="text-sm text-slate-500 mt-1">Daftar siswa yang harus diverifikasi dan ditempatkan ke kelas.</p>
                        </div>
                        @if($pendingStudents->total() > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-rose-50 px-3 py-1 text-sm font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20 whitespace-nowrap">{{ $pendingStudents->total() }} Menunggu</span>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Nama Siswa</th>
                                    <th class="px-6 py-4">NIS</th>
                                    <th class="px-6 py-4">Angkatan</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($pendingStudents as $student)
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-slate-200 overflow-hidden shrink-0"><x-avatar :url="$student->user->avatar_url" :name="$student->user->name" /></div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <p class="font-bold text-slate-900">{{ $student->user->name }}</p>
                                                        @if(empty($student->nis) || empty($student->year))
                                                            <span class="inline-flex items-center rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">Data Tidak Lengkap</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-xs text-slate-500">{{ $student->user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if(empty($student->nis) || empty($student->year))
                                                <span class="text-rose-500 font-medium italic text-xs">Menunggu Siswa</span>
                                            @else
                                                <span class="font-mono text-slate-700 font-medium">{{ $student->nis }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 font-medium">{{ $student->year ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">
                                            @if(empty($student->nis) || empty($student->year))
                                                <button disabled class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-400 cursor-not-allowed">Validasi & Tempatkan</button>
                                            @else
                                                <button wire:click="openValidateStudentModal({{ $student->id }})" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-1.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-500 opacity-80 group-hover:opacity-100">Validasi & Tempatkan</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500"><svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Tidak ada antrean validasi siswa.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($pendingStudents->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $pendingStudents->links() }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- TAB 3: CLASS ASSIGNMENTS -->
            @if($currentTab === 'classes')
            <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 50)" :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="space-y-6 md:space-y-8 transition-all duration-500 ease-out">
                
                <!-- Formulir Penempatan -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-slate-900">Buat Penempatan Kelas Baru</h3>
                        <p class="text-sm text-slate-500 mt-1">Petakan siswa tervalidasi ke kelas yang diampu oleh guru.</p>
                    </div>
                    <form wire:submit="assignClass" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 items-end">
                        
                        <div class="lg:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Guru</label>
                            <select wire:model="selectedTeacherId" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                <option value="">Pilih Guru...</option>
                                @foreach($allTeachers as $t)
                                    <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedTeacherId') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="lg:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Siswa</label>
                            <select wire:model="selectedStudentId" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                <option value="">Pilih Siswa...</option>
                                @foreach($allStudents as $s)
                                    <option value="{{ $s->id }}">{{ $s->user->name }} ({{ $s->nis }})</option>
                                @endforeach
                            </select>
                            @error('selectedStudentId') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="lg:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Nama Kelas</label>
                            <input type="text" wire:model="className" placeholder="Misal: X RPL A" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                            @error('className') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="lg:col-span-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Semester</label>
                            <select wire:model="semester" class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                <option value="1">Semester 1 (Ganjil)</option>
                                <option value="2">Semester 2 (Genap)</option>
                            </select>
                            @error('semester') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="lg:col-span-1">
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Daftarkan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabel Penempatan -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <h3 class="text-lg font-bold text-slate-900">Daftar Penempatan Aktif</h3>
                        <div class="relative w-full sm:w-72">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input wire:model.live.debounce.300ms="searchClass" type="text" class="bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 transition-colors" placeholder="Cari nama, kelas, semester...">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Nama Kelas</th>
                                    <th class="px-6 py-4">Semester</th>
                                    <th class="px-6 py-4">Guru Pengampu</th>
                                    <th class="px-6 py-4">Siswa</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($classAssignments as $assignment)
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="px-6 py-4 font-bold text-slate-900">{{ $assignment->class }}</td>
                                        <td class="px-6 py-4 text-slate-600">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">Sem {{ $assignment->semester }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700 font-medium">{{ $assignment->teacher->user->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-slate-700 font-medium">{{ $assignment->student->user->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-right opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button x-on:click.prevent="if(confirm('Yakin ingin menghapus penempatan kelas ini?')) { $wire.deleteAssignment({{ $assignment->id }}) }" class="text-rose-600 hover:text-rose-800 font-bold bg-white border border-rose-200 px-3 py-1.5 rounded-lg shadow-sm hover:bg-rose-50 transition text-xs flex items-center gap-1.5 ml-auto">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500"><svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>Belum ada penempatan kelas yang dibuat.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($classAssignments->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $classAssignments->links() }}
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </main>

    <!-- MODAL: VALIDASI & ASSIGNMENT SISWA -->
    @if($isValidateStudentModalOpen && $studentToValidate)
    <div class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" x-data="{ show: false }" x-init="setTimeout(() => show = true, 10)" :class="show ? 'opacity-100' : 'opacity-0'"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100" x-data="{ show: false }" x-init="setTimeout(() => show = true, 10)" :class="show ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95'">
                    
                    <form wire:submit="confirmValidationAndAssign">
                        <div class="bg-white px-6 pb-6 pt-6">
                            <div class="flex items-center space-x-4 mb-6 border-b border-slate-100 pb-4">
                                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900" id="modal-title">Validasi & Penempatan</h3>
                                    <p class="text-sm text-slate-500 mt-0.5">Siswa: <span class="font-bold text-slate-800">{{ $studentToValidate->user->name }}</span> <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-xs ml-1">{{ $studentToValidate->nis }}</span></p>
                                </div>
                            </div>
                            
                            <div class="space-y-5">
                                <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50 text-sm text-indigo-900/80 flex gap-3">
                                    <svg class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p>Dengan menekan tombol validasi, akun siswa ini akan diaktifkan secara publik dan langsung dipetakan ke dalam kelas berikut.</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Guru Pengampu</label>
                                    <select wire:model="selectedTeacherId" class="block w-full rounded-xl border-slate-200 bg-white py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors shadow-sm">
                                        <option value="">Pilih Guru...</option>
                                        @foreach($allTeachers as $t)
                                            <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedTeacherId') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Nama Kelas</label>
                                    <input type="text" wire:model="className" placeholder="Misal: X RPL A" class="block w-full rounded-xl border-slate-200 bg-white py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors shadow-sm">
                                    @error('className') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Semester</label>
                                    <select wire:model="semester" class="block w-full rounded-xl border-slate-200 bg-white py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors shadow-sm">
                                        <option value="1">Semester 1 (Ganjil)</option>
                                        <option value="2">Semester 2 (Genap)</option>
                                    </select>
                                    @error('semester') <span class="text-xs text-rose-600 mt-1.5 block font-medium">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50/80 backdrop-blur-xl px-6 py-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 rounded-b-3xl border-t border-slate-100">
                            <button type="button" wire:click="closeValidateStudentModal" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-500 sm:w-auto transition-colors">
                                Validasi & Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
