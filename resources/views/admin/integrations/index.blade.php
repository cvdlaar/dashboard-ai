@extends('layouts.app')

@section('title', 'Koppelingen & Synchronisatie')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Site tabs --}}
    @if($sites->count() > 1)
    <div class="flex space-x-1 bg-gray-100 p-1 rounded-xl w-fit">
        @foreach($sites as $s)
        <a href="?site_id={{ $s->id }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
               {{ $currentSite->id === $s->id
                   ? 'bg-white text-gray-900 shadow-sm'
                   : 'text-gray-600 hover:text-gray-900' }}">
            {{ $s->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- Site header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-lg bg-brand-blue flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm">{{ strtoupper(substr($currentSite->name, 0, 2)) }}</span>
            </div>
            <div class="ml-3">
                <h2 class="font-semibold text-gray-900">{{ $currentSite->name }}</h2>
                <p class="text-sm text-gray-500">{{ $currentSite->domain }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-400">Koppel een dienst en stel in wanneer data opgehaald wordt</p>
    </div>

    {{-- Platform kaarten --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($platforms as $key => $platform)
            @php
                $integration = $currentSite->integrations->firstWhere('platform', $key);
                $isConnected = $integration?->status === 'connected';
                $hasError    = $integration?->status === 'error';
                $schedule    = $integration?->sync_schedule ?? 'manual';
                $lastSync    = $integration?->last_sync_at;
                $lastLog     = $lastLogs[$key] ?? null;
                $logOk       = $lastLog?->status === 'completed';
                $logFailed   = $lastLog?->status === 'failed';

                $icons = [
                    'google_search_console' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                    'google_analytics'      => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'google_ads'            => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'bing_ads'              => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'channable'             => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    'pagespeed'             => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'sitemap'               => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                ];
            @endphp

            <div class="border rounded-xl p-4 flex flex-col gap-3
                {{ $hasError ? 'border-red-200 bg-red-50' : ($isConnected ? 'border-gray-200 bg-white' : 'border-gray-200 bg-white') }}">

                {{-- Header: logo + naam + status --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        @if($platform['icon'] === 'google')
                            <div class="w-9 h-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-4 h-4" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </div>
                        @elseif($platform['icon'] === 'microsoft')
                            <div class="w-9 h-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-4 h-4" viewBox="0 0 24 24">
                                    <path d="M11.4 24H0V12.6h11.4V24z" fill="#F25022"/>
                                    <path d="M24 24H12.6V12.6H24V24z" fill="#00A4EF"/>
                                    <path d="M11.4 11.4H0V0h11.4v11.4z" fill="#7FBA00"/>
                                    <path d="M24 11.4H12.6V0H24v11.4z" fill="#FFB900"/>
                                </svg>
                            </div>
                        @elseif($platform['icon'] === 'channable')
                            <div class="w-9 h-9 rounded-lg bg-brand-orange flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-xs font-bold">CH</span>
                            </div>
                        @else
                            <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$key] ?? '' }}"/>
                                </svg>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $platform['label'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $platform['auth'] === 'oauth' ? 'OAuth 2.0' : ($platform['auth'] === 'api_key' ? 'API sleutel' : 'Geen auth') }}
                            </p>
                        </div>
                    </div>

                    @if($isConnected)
                        <span class="badge-good flex-shrink-0">Verbonden</span>
                    @elseif($hasError)
                        <span class="badge-poor flex-shrink-0">Fout</span>
                    @elseif($platform['auth'] === 'none')
                        <span class="inline-flex items-center bg-blue-100 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0">Altijd actief</span>
                    @else
                        <span class="inline-flex items-center bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0">Niet ingesteld</span>
                    @endif
                </div>

                {{-- Laatste sync status --}}
                <div class="text-xs text-gray-500 min-h-[2rem]">
                    @if($lastLog)
                        <div class="flex items-center">
                            <span class="w-2 h-2 rounded-full mr-1.5 flex-shrink-0 {{ $logOk ? 'bg-green-500' : ($logFailed ? 'bg-red-500' : 'bg-yellow-400') }}"></span>
                            <span>{{ $logOk ? 'Geslaagd' : ($logFailed ? 'Mislukt' : 'Bezig') }}</span>
                            @if($lastLog->finished_at)
                                <span class="mx-1 text-gray-300">·</span>
                                <span>{{ $lastLog->finished_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        @if($logOk && ($lastLog->records_new + $lastLog->records_updated) > 0)
                            <div class="text-gray-400 mt-0.5">{{ $lastLog->records_new }} nieuw, {{ $lastLog->records_updated }} bijgewerkt</div>
                        @endif
                        @if($logFailed && $lastLog->message)
                            <div class="text-red-500 truncate mt-0.5" title="{{ $lastLog->message }}">{{ Str::limit($lastLog->message, 60) }}</div>
                        @endif
                    @else
                        <span class="italic text-gray-400">Nog niet gesynchroniseerd</span>
                    @endif
                </div>

                {{-- Sync schema --}}
                @if($isConnected || $platform['auth'] === 'none')
                <form method="POST" action="{{ route('admin.integrations.sync', [$currentSite, $key]) }}"
                      id="sync-form-{{ $key }}" class="flex items-center gap-2">
                    @csrf
                    <select name="sync_schedule"
                            onchange="this.form.submit()"
                            data-schedule-form="{{ $key }}"
                            class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 text-gray-700 focus:ring-1 focus:ring-brand-blue focus:border-brand-blue">
                        <option value="manual"  {{ $schedule === 'manual'  ? 'selected' : '' }}>Handmatig</option>
                        <option value="daily"   {{ $schedule === 'daily'   ? 'selected' : '' }}>Dagelijks</option>
                        <option value="weekly"  {{ $schedule === 'weekly'  ? 'selected' : '' }}>Wekelijks</option>
                    </select>
                    <input type="hidden" name="_action" value="schedule">
                </form>
                <form method="POST" action="{{ route('admin.integrations.sync', [$currentSite, $key]) }}">
                    @csrf
                    <input type="hidden" name="_action" value="run">
                    <button type="submit"
                            onclick="return confirm('{{ $platform['label'] }} starten voor {{ $currentSite->name }}?')"
                            class="w-full text-xs font-medium px-3 py-1.5 rounded-lg bg-brand-blue text-white hover:opacity-90 transition-opacity flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ $logOk ? 'Opnieuw synchroniseren' : 'Nu synchroniseren' }}
                    </button>
                </form>
                @endif

                {{-- Instellen knop --}}
                @if($platform['auth'] !== 'none')
                <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
                    <a href="{{ route('admin.integrations.edit', [$currentSite, $key]) }}"
                       class="flex-1 text-center text-xs font-medium px-3 py-1.5 rounded-lg border border-brand-blue text-brand-blue hover:bg-brand-blue hover:text-white transition-colors">
                        {{ $isConnected ? 'Bewerken' : 'Instellen' }}
                    </a>
                    @if($isConnected)
                    <form method="POST" action="{{ route('admin.integrations.destroy', [$currentSite, $key]) }}"
                          onsubmit="return confirm('Koppeling voor {{ $platform['label'] }} verwijderen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
                            Verwijderen
                        </button>
                    </form>
                    @endif
                </div>
                @endif

            </div>
        @endforeach
    </div>

</div>
@endsection
