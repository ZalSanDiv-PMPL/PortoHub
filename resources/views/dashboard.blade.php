<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @if(auth()->user()->isTeacher())
        <livewire:dashboard.teacher />
    @elseif(auth()->user()->isAdmin())
        <livewire:dashboard.admin />
    @else
        <livewire:dashboard.student />
    @endif
</x-app-layout>
