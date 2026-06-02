<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public $notifications = [];
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Load the latest 10 notifications
            $this->notifications = $user->notifications()->take(10)->get();
            $this->unreadCount = $user->unreadNotifications()->count();
        }
    }

    public function markAsRead($notificationId)
    {
        if (Auth::check()) {
            $notification = Auth::user()->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
            }
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
            $this->loadNotifications();
        }
    }
}; ?>

<div class="relative inline-block text-left" id="notification-dropdown-container" wire:poll.15s="loadNotifications">
    <button type="button" id="notification-menu-button" class="relative text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-full p-1 transition-colors">
        <span class="sr-only">View notifications</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 flex h-3 w-3 items-center justify-center">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-600 ring-2 ring-white"></span>
            </span>
        @endif
    </button>

    <div id="notification-menu" class="hidden absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-slate-900/5 focus:outline-none overflow-hidden" role="menu">
        <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="text-sm font-semibold text-slate-900">Notifikasi</h3>
            @if($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">Tandai dibaca</button>
            @endif
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="block px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition cursor-pointer {{ is_null($notification->read_at) ? 'bg-blue-50/30' : '' }}" 
                     wire:click="markAsRead('{{ $notification->id }}')">
                    <p class="text-sm text-slate-800 {{ is_null($notification->read_at) ? 'font-medium' : '' }}">
                        {{ $notification->data['message'] ?? 'Notifikasi baru' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center flex flex-col items-center justify-center">
                    <svg class="h-8 w-8 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="text-sm text-slate-500">Tidak ada notifikasi baru.</span>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function initNotificationDropdown() {
            const btn = document.getElementById('notification-menu-button');
            const menu = document.getElementById('notification-menu');

            if(btn && menu && !btn.dataset.initNotif) {
                btn.dataset.initNotif = 'true';

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if(!menu.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initNotificationDropdown);
        document.addEventListener('livewire:navigated', initNotificationDropdown);
        initNotificationDropdown();
    </script>
</div>
