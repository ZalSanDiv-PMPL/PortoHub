<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-academic-info-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium">GitHub Integration</h3>
                    <p class="text-sm text-gray-500 mt-1">Connect your GitHub account to sync repository metadata and
                        link projects.</p>

                    @php $token = auth()->user()->githubToken ?? null; @endphp
                    @php $user = auth()->user(); @endphp

                    <div class="mt-4">
                        @if($token)
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold">Connected as {{ $token->github_username }}</div>
                                <div class="text-sm text-gray-500">GitHub ID: {{ $token->github_id }}</div>
                            </div>
                            @if($user?->hasLocalPassword())
                            <form method="POST" action="{{ route('github.unlink') }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    Disconnect
                                </button>
                            </form>
                            @else
                            <button type="button" disabled
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-300 cursor-not-allowed"
                                title="Set a password first">
                                Disconnect
                            </button>
                            @endif
                        </div>

                        @if(! $user?->hasLocalPassword())
                        <div class="mt-3 rounded-md bg-blue-50 p-3">
                            <p class="text-sm text-blue-800">Set a password above before disconnecting GitHub, otherwise
                                you may lose access to this account.</p>
                        </div>
                        @endif

                        @if(empty($token->refresh_token))
                        <div class="mt-3 rounded-md bg-yellow-50 p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor"
                                        aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.516 9.818c.75 1.335-.213 2.983-1.742 2.983H4.483c-1.53 0-2.492-1.648-1.742-2.983L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-.25-6a.75.75 0 00-1.5 0v3.5a.75.75 0 001.5 0V7z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Refresh token not available</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>GitHub did not provide a refresh token for this connection. This means your
                                            access may expire and you will need to reconnect to GitHub to restore
                                            access.</p>
                                        <p class="mt-2">To renew access, click <a href="{{ route('github.link') }}"
                                                class="font-medium underline">Reconnect GitHub</a>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @else
                        <a href="{{ route('github.link') }}"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Connect GitHub
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>