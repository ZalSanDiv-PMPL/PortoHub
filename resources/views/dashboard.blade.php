<x-app-layout>
    <div class="py-12">
        @if(auth()->user()->isTeacher())
            <livewire:dashboard.teacher />
        @elseif(auth()->user()->isAdmin())
            <livewire:dashboard.admin />
        @else
            <livewire:dashboard.student />
        @endif
    </div>
</x-app-layout>
