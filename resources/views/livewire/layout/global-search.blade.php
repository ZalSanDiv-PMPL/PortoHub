<div class="relative hidden sm:block w-full sm:w-64 md:w-80" x-data="{ open: false }" @click.outside="open = false">
    <!-- Input Field -->
    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <!-- Loading Spinner (shown when processing) -->
            <svg wire:loading wire:target="query" class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <!-- Magnifying Glass Icon (shown normally) -->
            <svg wire:loading.remove wire:target="query" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input 
            wire:model.live.debounce.300ms="query"
            @focus="open = true"
            @keydown.escape.window="open = false"
            type="text" 
            class="block w-full rounded-md border-0 bg-slate-200/70 py-1.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 transition" 
            placeholder="Cari siswa atau proyek..."
            autocomplete="off">
    </div>

    <!-- Dropdown Results -->
    <div x-show="open && $wire.query.length >= 2" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute z-50 mt-2 w-full origin-top-right rounded-xl bg-white py-2 shadow-lg ring-1 ring-slate-900/5 focus:outline-none overflow-hidden max-h-96 overflow-y-auto"
         style="display: none;">
        
        @if(count($studentResults) === 0 && count($projectResults) === 0)
            <div class="px-4 py-3 text-sm text-slate-500 text-center">
                Tidak ada hasil untuk "<span class="font-semibold text-slate-700">{{ $query }}</span>"
            </div>
        @else
            <!-- Students Section -->
            @if(count($studentResults) > 0)
                <div class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                    Siswa
                </div>
                <ul>
                    @foreach($studentResults as $student)
                        <li>
                            <a href="{{ route('student.profile', ['username' => $student['username']]) }}" class="block px-4 py-2 hover:bg-slate-50 transition" wire:navigate>
                                <div class="flex items-center gap-3">
                                    @php
                                        // Simple fallback avatar for search results
                                        $avatar = $student['avatar_path'] 
                                            ? Storage::disk('public')->url($student['avatar_path']) 
                                            : 'https://ui-avatars.com/api/?name='.urlencode($student['name']).'&color=4F46E5&background=E0E7FF';
                                    @endphp
                                    <img src="{{ $avatar }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $student['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ '@' . $student['username'] }}</p>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <!-- Divider -->
            @if(count($studentResults) > 0 && count($projectResults) > 0)
                <div class="border-t border-slate-100 my-1"></div>
            @endif

            <!-- Projects Section -->
            @if(count($projectResults) > 0)
                <div class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                    Portofolio Proyek
                </div>
                <ul>
                    @foreach($projectResults as $project)
                        <li>
                            <a href="{{ route('project.show', ['project' => $project['id']]) }}" class="block px-4 py-2 hover:bg-slate-50 transition" wire:navigate>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ $project['title'] }}</p>
                                    <p class="text-xs text-slate-500">Oleh {{ $project['author_name'] }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</div>
