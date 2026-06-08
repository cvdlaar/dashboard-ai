@extends('layouts.app')

@section('title', 'Overzicht')

@section('content')
<div class="space-y-6">

    {{-- KPI row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal pagina's</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalPages) }}</p>
            <div class="flex items-center mt-2 text-xs text-gray-500">
                <span class="w-2 h-2 rounded-full bg-brand-blue mr-1.5"></span>{{ $productPages }} producten
                <span class="w-2 h-2 rounded-full bg-brand-orange ml-3 mr-1.5"></span>{{ $categoryPages }} categorieën
            </div>
        </div>

        <div class="card">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Gem. positie (GSC)</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $avgPosition ? number_format($avgPosition, 1) : '–' }}</p>
            <p class="text-xs text-gray-500 mt-2">Afgelopen 28 dagen</p>
        </div>

        <div class="card">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">CWV score (gem.)</p>
            <p class="text-3xl font-bold mt-1 {{ $avgCwv >= 90 ? 'text-green-600' : ($avgCwv >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ $avgCwv ? $avgCwv : '–' }}
            </p>
            <p class="text-xs text-gray-500 mt-2">PageSpeed (mobiel)</p>
        </div>

        <div class="card">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Open prioriteiten</p>
            <p class="text-3xl font-bold text-brand-orange mt-1">{{ $openPriorities }}</p>
            <p class="text-xs text-gray-500 mt-2">Over alle categorieën</p>
        </div>
    </div>

    {{-- Two column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top kansen (quick wins) --}}
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900">Top kansen deze week</h2>
                <a href="{{ route('priorities.index') }}" class="text-xs text-brand-blue hover:underline">Alle bekijken</a>
            </div>
            @if($topOpportunities->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">Nog geen data beschikbaar — voer een sync uit.</p>
            @else
            <div class="space-y-3">
                @foreach($topOpportunities as $page)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex-1 min-w-0 mr-4">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $page->title ?? $page->path }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $page->url }}</p>
                    </div>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <span class="text-xs font-semibold text-brand-orange">{{ $page->priority_score }}pt</span>
                        <span class="badge-{{ $page->priority_label === 'Hoog' ? 'poor' : 'warning' }}">{{ $page->priority_label }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- CWV alerts --}}
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900">CWV – aandacht nodig</h2>
                <a href="{{ route('cwv.index') }}" class="text-xs text-brand-blue hover:underline">Alle bekijken</a>
            </div>
            @if($cwvAlerts->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">Geen CWV data — voer een scan uit.</p>
            @else
            <div class="space-y-3">
                @foreach($cwvAlerts as $metric)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1 min-w-0 mr-4">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $metric->page->title ?? $metric->page->path }}</p>
                        <div class="flex items-center space-x-2 mt-1">
                            @if($metric->lcp_rating === 'poor')
                                <span class="badge-poor">LCP: {{ number_format($metric->lcp, 1) }}s</span>
                            @endif
                            @if($metric->cls_rating === 'poor')
                                <span class="badge-poor">CLS: {{ $metric->cls }}</span>
                            @endif
                            @if($metric->inp_rating === 'poor')
                                <span class="badge-poor">INP: {{ number_format($metric->inp) }}ms</span>
                            @endif
                        </div>
                    </div>
                    <span class="text-2xl font-bold {{ $metric->performance_score < 50 ? 'text-red-600' : 'text-yellow-600' }} flex-shrink-0">
                        {{ $metric->performance_score }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Sites overview --}}
    <div class="card">
        <h2 class="font-semibold text-gray-900 mb-4">Websites</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($sites as $site)
            <a href="{{ route('pages.index', ['site' => $site->id]) }}" class="flex items-center p-4 border border-gray-200 rounded-xl hover:border-brand-blue hover:bg-blue-50 transition-colors group">
                <div class="w-10 h-10 rounded-lg bg-brand-blue flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($site->name, 0, 2)) }}</span>
                </div>
                <div class="ml-4 flex-1">
                    <p class="font-medium text-gray-900 group-hover:text-brand-blue">{{ $site->name }}</p>
                    <p class="text-sm text-gray-500">{{ $site->domain }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">{{ number_format($site->pages_count ?? 0) }}</p>
                    <p class="text-xs text-gray-500">pagina's</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>

</div>
@endsection
