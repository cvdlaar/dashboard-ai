<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Dashboard') }} - @yield('title', 'Overzicht')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50">

    {{-- Sidebar --}}
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-brand-blue flex-shrink-0 flex flex-col">
            {{-- Logo --}}
            <div class="flex items-center h-16 px-6 border-b border-blue-700">
                <span class="text-white font-bold text-lg tracking-tight">LC Dashboard</span>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Overzicht
                </a>

                <a href="{{ route('pages.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('pages.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Pagina's
                </a>

                <a href="{{ route('priorities.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('priorities.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Prioriteiten
                </a>

                <div class="pt-2 pb-1 px-3">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Organisch</p>
                </div>

                <a href="{{ route('seo.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('seo.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    SEO & Search Console
                </a>

                <a href="{{ route('cwv.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('cwv.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Core Web Vitals
                </a>

                <a href="{{ route('geo.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('geo.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    GEO / AI-zichtbaarheid
                </a>

                <div class="pt-2 pb-1 px-3">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Betaald</p>
                </div>

                <a href="{{ route('ads.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('ads.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Advertenties
                </a>

                @if(auth()->user()->hasRole('admin'))
                <div class="pt-2 pb-1 px-3">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Beheer</p>
                </div>

                <a href="{{ route('admin.sites.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.sites.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    Websites
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Gebruikers
                </a>

                <a href="{{ route('admin.integrations.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.integrations.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Koppelingen & Sync
                </a>

                <a href="{{ route('admin.product-groups.index') }}"
                   class="flex items-center px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.product-groups.*') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/10 hover:text-white' }} transition-colors">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Productgroepen
                </a>
                @endif
            </nav>

            {{-- User footer --}}
            <div class="border-t border-blue-700 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center min-w-0">
                        <div class="w-8 h-8 rounded-full bg-brand-orange flex items-center justify-center flex-shrink-0">
                            <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-blue-300 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-blue-300 hover:text-white transition-colors ml-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 h-16 flex items-center px-6 flex-shrink-0">
                <div class="flex items-center justify-between w-full">
                    <h1 class="text-lg font-semibold text-gray-900">@yield('title', 'Overzicht')</h1>
                    <div class="flex items-center space-x-3">
                        {{-- Site selector --}}
                        @php $allSites = \App\Models\Site::where('is_active', true)->get(); @endphp
                        @if($allSites->count() > 1)
                        <form method="POST" action="{{ route('select-site') }}" id="site-selector-form">
                            @csrf
                            <select name="site_id"
                                    onchange="document.getElementById('site-selector-form').submit()"
                                    class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue pr-8">
                                @foreach($allSites as $s)
                                    <option value="{{ $s->id }}" {{ session('current_site_id', $allSites->first()->id) == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        @endif
                        @yield('header-actions')
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
