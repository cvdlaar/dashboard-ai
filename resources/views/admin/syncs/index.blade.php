@extends('layouts.app')
@section('title', 'Data synchronisatie')
@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @foreach($sites as $site)
    <div class="card">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 rounded-lg bg-brand-blue flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm">{{ strtoupper(substr($site->name, 0, 2)) }}</span>
            </div>
            <div class="ml-3">
                <h2 class="font-semibold text-gray-900">{{ $site->name }}</h2>
                <p class="text-sm text-gray-500">{{ $site->domain }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach($types as $typeKey => $typeLabel)
                @php
                    $log = $lastSyncs[$site->id][$typeKey] ?? null;
                    $isCompleted = $log?->status === 'completed';
                    $isFailed = $log?->status === 'failed';

                    $typeIcons = [
                        'sitemap'    => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                        'channable'  => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        'pagespeed'  => 'M13 10V3L4 14h7v7l9-11h-7z',
                        'gsc'        => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                        'ga4'        => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        'google_ads' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        'bing_ads'   => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    ];
                @endphp

                <div class="border rounded-xl p-4 {{ $isFailed ? 'border-red-200 bg-red-50' : ($isCompleted ? 'border-gray-200' : 'border-gray-200') }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mr-2.5 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIcons[$typeKey] ?? '' }}"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-900 leading-tight">{{ $typeLabel }}</p>
                        </div>
                    </div>

                    {{-- Laatste sync info --}}
                    <div class="text-xs text-gray-500 mb-3 space-y-0.5 min-h-[2.5rem]">
                        @if($log)
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full mr-1.5 flex-shrink-0 {{ $isCompleted ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $isCompleted ? 'Geslaagd' : 'Mislukt' }}
                                @if($log->finished_at) · {{ $log->finished_at->diffForHumans() }} @endif
                            </div>
                            @if($isCompleted && ($log->records_new + $log->records_updated) > 0)
                                <div class="text-gray-400">{{ $log->records_new }} nieuw, {{ $log->records_updated }} bijgewerkt</div>
                            @endif
                            @if($isFailed && $log->message)
                                <div class="text-red-500 truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 50) }}</div>
                            @endif
                        @else
                            <span class="text-gray-400 italic">Nog niet gesynchroniseerd</span>
                        @endif
                    </div>

                    {{-- Run knop --}}
                    <form method="POST" action="{{ route('admin.syncs.run', [$site, $typeKey]) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('{{ $typeLabel }} starten voor {{ $site->name }}?')"
                                class="w-full text-xs font-medium px-3 py-1.5 rounded-lg bg-brand-blue text-white hover:bg-brand-blue-dark transition-colors flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ $isCompleted ? 'Opnieuw sync' : 'Start sync' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach

</div>
@endsection
