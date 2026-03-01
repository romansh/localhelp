<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('ui.app_name') }} — {{ __('ui.tagline') }}</title>

    {{-- Leaflet CSS + JS (CDN) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    {{-- App CSS + JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
    {{-- Header --}}
    <header class="fixed top-0 inset-x-0 z-[1000] bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-4 h-14">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-lg text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('ui.app_name') }}
            </a>

            {{-- Right side: locale + auth --}}
            <div class="flex items-center gap-3">
                {{-- Locale switcher --}}
                <div class="flex items-center gap-1 text-sm">
                    @foreach (config('localhelp.locale.available', ['en']) as $loc)
                        <a href="{{ route('locale.switch', $loc) }}"
                           class="px-2 py-1 rounded {{ app()->getLocale() === $loc ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-500 hover:text-gray-700' }}">
                            {{ strtoupper($loc === 'uk' ? 'UA' : $loc) }}
                        </a>
                    @endforeach
                </div>

                {{-- Auth --}}
                @auth
                    <div x-data="{ showNeeds: false, showHelp: false }" class="flex items-center gap-2">
                        @php
                            $name = trim(auth()->user()->name ?? '');
                            $initials = '';
                            if ($name !== '') {
                                $parts = preg_split('/\s+/', $name);
                                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                            }
                        @endphp
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full"
                                 onerror="this.outerHTML='<span class=\'w-8 h-8 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center font-medium\'>{{ $initials }}<\/span>'" />
                        @else
                            <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center font-medium">{{ $initials }}</span>
                        @endif
                        <span class="hidden sm:inline text-sm text-gray-700">{{ auth()->user()->name }}</span>
                        {{-- Quick links: My needs / My help --}}
                        <button @click.prevent="showNeeds = true" class="ml-2 text-xs text-gray-600 hover:text-indigo-600">{{ __('ui.my_needs') }}</button>
                        <button @click.prevent="showHelp = true" class="ml-2 text-xs text-gray-600 hover:text-indigo-600">{{ __('ui.my_help') }}</button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-red-600 transition cursor-pointer">
                                {{ __('auth.logout') }}
                            </button>
                        </form>
                        
                        {{-- Modals --}}
                        <div x-show="showNeeds" x-cloak class="fixed inset-0 z-[1600] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/40" @click="showNeeds = false"></div>
                            <div class="relative z-10">
                                <livewire:my-needs />
                            </div>
                        </div>

                        <div x-show="showHelp" x-cloak class="fixed inset-0 z-[1600] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/40" @click="showHelp = false"></div>
                            <div class="relative z-10">
                                <livewire:my-help />
                            </div>
                        </div>
                    </div>
                @else
                    <a href="{{ route('auth.google') }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        {{ __('auth.login_with_google') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main class="pt-14 h-full">
        {{ $slot }}
    </main>

    {{-- Toast container --}}
    <div x-data="toastManager()"
         @toast.window="addToast($event.detail)"
         class="fixed bottom-4 right-4 z-[2000] flex flex-col gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-xs"
                 :class="{
                     'bg-green-600': toast.type === 'success',
                     'bg-red-600': toast.type === 'error',
                     'bg-yellow-500 text-gray-900': toast.type === 'warning',
                     'bg-indigo-600': toast.type === 'info'
                 }"
                 x-text="toast.message">
            </div>
        </template>
    </div>

    @livewireScripts

    <script>
        // Toast notification manager
        function toastManager() {
            return {
                toasts: [],
                nextId: 0,
                addToast({ message, type = 'info' }) {
                    const id = this.nextId++;
                    this.toasts.push({ id, message, type, visible: true });
                    setTimeout(() => {
                        const toast = this.toasts.find(t => t.id === id);
                        if (toast) toast.visible = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }, 3000);
                }
            };
        }
    </script>
</body>
</html>
