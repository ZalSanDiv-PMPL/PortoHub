<x-app-layout>
    @if(auth()->user()->isAdmin())
        <!-- Admin Dashboard (Full Height without padding) -->
        <livewire:dashboard.admin />
    @else
        <!-- Teacher & Student Dashboards (with standard padding) -->
        <div class="py-12">
            @if(auth()->user()->isTeacher())
                <livewire:dashboard.teacher />
            @else
                <livewire:dashboard.student />
            @endif
        </div>
    @endif
</x-app-layout>
