@extends('layouts.app')
@section('title', $page->title ?? $page->path)
@section('content')
<div class="space-y-6 max-w-5xl">

    {{-- Terug + header --}}
    <div>
        <a href="{{ route('pages.index') }}" class="text-sm text-brand-blue hover:underline flex items-center mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Terug naar pagina's
        </a>
        <div class="card">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-gray-900 text-base">{{ $page->title ?? '(geen titel)' }}</h2>
                    <a href="{{ $page->url }}" target="_blank" class="text-sm text-brand-blue hover:underline break-all">{{ $page->url }}</a>
                </div>
                <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full text-sm font-bold
                    {{ $page->priority_score >= 70 ? 'bg-red-100 text-red-600' : ($page->priority_score >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                    {{ $page->priority_score }}
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="inline-flex bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded-full capitalize">{{ $page->type }}</span>
                <span class="inline-flex bg-gray-100 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full">{{ strtoupper($page->language) }}</span>
                @if($page->has_json_ld) <span class="badge-good">JSON-LD</span> @endif
                @if($page->categoryOwner)
                    <span class="inline-flex bg-blue-100 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ $page->categoryOwner->name }}</span>
                @endif
                <span class="inline-flex bg-gray-50 border border-gray-200 text-gray-500 text-xs px-2 py-0.5 rounded-full">{{ $page->site->name }}</span>
            </div>
        </div>
    </div>

    {{-- Drie scores boven elkaar --}}
    <div class="grid grid-cols-3 gap-4">
        @php
            $cwv = $page->latestCwvMetric;
            $gsc = $page->latestGscMetric;
            $geo = $page->latestGeoMetric;
        @endphp

        <div class="card text-center">
            @if($cwv)
                <p class="text-4xl font-bold {{ $cwv->performance_score >= 90 ? 'text-green-600' : ($cwv->performance_score >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                    {{ $cwv->performance_score }}
                </p>
                <p class="text-xs text-gray-500 mt-1">CWV Performance</p>
                <p class="text-xs text-gray-400">mobiel · {{ $cwv->created_at->format('d M') }}</p>
            @else
                <p class="text-2xl font-bold text-gray-200">–</p>
                <p class="text-xs text-gray-400 mt-1">CWV niet gescand</p>
            @endif
        </div>

        <div class="card text-center">
            @if($gsc && $gsc->position)
                <p class="text-4xl font-bold {{ $gsc->position <= 3 ? 'text-green-600' : ($gsc->position <= 10 ? 'text-amber-500' : 'text-gray-500') }}">
                    {{ number_format($gsc->position, 1) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">GSC Positie</p>
                <p class="text-xs text-gray-400">{{ number_format($gsc->impressions) }} imp · {{ number_format($gsc->ctr * 100, 1) }}% CTR</p>
            @else
                <p class="text-2xl font-bold text-gray-200">–</p>
                <p class="text-xs text-gray-400 mt-1">GSC geen data</p>
            @endif
        </div>

        <div class="card text-center">
            @if($geo)
                <p class="text-4xl font-bold {{ $geo->geo_score >= 75 ? 'text-green-600' : ($geo->geo_score >= 45 ? 'text-amber-500' : 'text-red-500') }}">
                    {{ $geo->geo_score }}
                </p>
                <p class="text-xs text-gray-500 mt-1">GEO / AI-score</p>
                <p class="text-xs text-gray-400">{{ $geo->scored_at->format('d M') }}</p>
            @else
                <p class="text-2xl font-bold text-gray-200">–</p>
                <p class="text-xs text-gray-400 mt-1">GEO niet gescand</p>
            @endif
        </div>
    </div>

    {{-- Detail kaarten: CWV + GSC naast elkaar --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- CWV detail --}}
        <div class="card">
            <h3 class="font-semibold text-gray-900 mb-4 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Core Web Vitals
            </h3>
            @if($cwv)
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">LCP <span class="text-xs text-gray-400">(≤2.5s goed)</span></span>
                    <span class="badge-{{ $cwv->lcp_rating ?? 'warning' }}">{{ number_format($cwv->lcp, 2) }}s</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">CLS <span class="text-xs text-gray-400">(≤0.1 goed)</span></span>
                    <span class="badge-{{ $cwv->cls_rating ?? 'warning' }}">{{ $cwv->cls }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">INP <span class="text-xs text-gray-400">(≤200ms goed)</span></span>
                    <span class="badge-{{ $cwv->inp_rating ?? 'warning' }}">{{ number_format($cwv->inp) }}ms</span>
                </div>
                <div class="flex justify-between items-center pt-1 border-t border-gray-100">
                    <span class="text-gray-600">Desktop score</span>
                    <span class="text-sm font-semibold text-gray-700">{{ $cwv->desktop_score ?? '–' }}</span>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400 py-4 text-center">Nog geen CWV data — voer <code class="bg-gray-100 px-1 rounded text-xs">scan:cwv</code> uit.</p>
            @endif
        </div>

        {{-- GSC detail --}}
        <div class="card">
            <h3 class="font-semibold text-gray-900 mb-4 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search Console
            </h3>
            @if($gsc)
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Gemiddelde positie</span><span class="font-semibold {{ $gsc->position <= 3 ? 'text-green-600' : ($gsc->position <= 10 ? 'text-amber-600' : 'text-gray-700') }}">{{ number_format($gsc->position, 1) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Impressies</span><span class="font-semibold">{{ number_format($gsc->impressions) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Clicks</span><span class="font-semibold">{{ number_format($gsc->clicks) }}</span></div>
                <div class="flex justify-between">
                    <span class="text-gray-600">CTR</span>
                    @php $expectedCtr = $gsc->position <= 3 ? 0.10 : 0.03; @endphp
                    <span class="font-semibold {{ $gsc->ctr >= $expectedCtr ? 'text-green-600' : 'text-amber-600' }}">
                        {{ number_format($gsc->ctr * 100, 1) }}%
                        @if($gsc->ctr < $expectedCtr)
                            <span class="text-xs font-normal text-gray-400">(verwacht {{ number_format($expectedCtr * 100, 0) }}%+)</span>
                        @endif
                    </span>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400 py-4 text-center">Nog geen GSC data — verbind Search Console.</p>
            @endif
        </div>
    </div>

    {{-- GEO inzichten --}}
    @if($geo)
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                GEO / AI-zichtbaarheid — inzichten
            </h3>
            <span class="text-xs text-gray-400">Gescand {{ $geo->scored_at->diffForHumans() }}</span>
        </div>

        {{-- Criteria raster --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 mb-5">
            @php
                $checks = [
                    ['label' => 'JSON-LD',             'val' => $geo->has_json_ld,            'pts' => 5],
                    ['label' => 'FAQ schema',           'val' => $geo->has_faq_schema,         'pts' => 5],
                    ['label' => 'Product schema',       'val' => $geo->has_product_schema,     'pts' => 3],
                    ['label' => 'Breadcrumb schema',    'val' => $geo->has_breadcrumb_schema,  'pts' => 2],
                    ['label' => 'Organisatie schema',   'val' => $geo->has_organization_schema,'pts' => 4],
                    ['label' => 'Auteur schema',        'val' => $geo->has_author_schema,      'pts' => 3],
                    ['label' => 'Open Graph',           'val' => $geo->has_open_graph,         'pts' => 3],
                    ['label' => 'Canonical',            'val' => $geo->has_canonical,          'pts' => 2],
                    ['label' => 'Meta description',     'val' => $geo->has_meta_description,   'pts' => 3],
                    ['label' => 'H1 aanwezig',          'val' => $geo->has_h1,                 'pts' => 3],
                    ['label' => 'Inhoudsopgave',        'val' => $geo->has_table_of_contents,  'pts' => 3],
                    ['label' => 'Lijsten/tabellen',     'val' => $geo->has_list_content,       'pts' => 4],
                    ['label' => 'Citeerbare intro',     'val' => $geo->has_citable_intro,      'pts' => 12],
                    ['label' => 'FAQ-blok',             'val' => $geo->has_faq_block,          'pts' => 8],
                    ['label' => 'Vergelijkende copy',   'val' => $geo->has_comparison_content, 'pts' => 8],
                    ['label' => 'Externe bronlinks',    'val' => $geo->external_link_count >= 3,'pts' => 7],
                    ['label' => 'dateModified',         'val' => $geo->date_modified !== null, 'pts' => 15],
                ];
            @endphp
            @foreach($checks as $check)
            <div class="flex items-center gap-2 p-2 rounded-lg {{ $check['val'] ? 'bg-green-50' : 'bg-red-50' }}">
                <div class="flex-shrink-0">
                    @if($check['val'])
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium {{ $check['val'] ? 'text-green-800' : 'text-red-700' }} leading-tight">{{ $check['label'] }}</p>
                    <p class="text-xs {{ $check['val'] ? 'text-green-500' : 'text-red-400' }}">{{ $check['pts'] }} pts</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Aandachtspunten --}}
        @if(!empty($geo->issues))
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Aandachtspunten</p>
            <ul class="space-y-2">
                @foreach($geo->issues as $issue)
                <li class="flex items-start gap-2 text-sm">
                    <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-gray-700">{{ $issue }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @else
        <div class="border-t border-gray-100 pt-4">
            <p class="text-sm text-green-600 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Geen aandachtspunten — pagina voldoet aan alle GEO-criteria.
            </p>
        </div>
        @endif

        {{-- Extra details --}}
        <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-4 mt-4 text-sm text-center">
            <div>
                <p class="text-2xl font-bold text-gray-700">{{ $geo->question_heading_count }}</p>
                <p class="text-xs text-gray-400">Vraagkoppen (H2/H3)</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-700">{{ $geo->external_link_count }}</p>
                <p class="text-xs text-gray-400">Externe bronlinks</p>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $geo->freshness_days !== null && $geo->freshness_days <= 90 ? 'text-green-600' : 'text-amber-500' }}">
                    {{ $geo->freshness_days !== null ? $geo->freshness_days.'d' : '–' }}
                </p>
                <p class="text-xs text-gray-400">Dagen sinds update</p>
            </div>
        </div>
    </div>
    @else
    <div class="card text-center py-8">
        <p class="text-sm text-gray-400">Nog geen GEO-analyse voor deze pagina.</p>
        <code class="block mt-2 text-xs bg-gray-100 rounded px-3 py-1.5 w-fit mx-auto">php artisan scan:geo --site={{ $page->site->domain }}</code>
    </div>
    @endif

    {{-- Channable / product --}}
    @if($page->channableData)
    @php $ch = $page->channableData; @endphp
    <div class="card">
        <h3 class="font-semibold text-gray-900 mb-4 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Product (Channable)
        </h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div><p class="text-xs text-gray-400 mb-0.5">Prijs</p><p class="font-semibold">€ {{ number_format($ch->price, 2) }}</p></div>
            @if($ch->margin)<div><p class="text-xs text-gray-400 mb-0.5">Marge</p><p class="font-semibold text-green-600">{{ number_format($ch->margin * 100, 1) }}%</p></div>@endif
            <div><p class="text-xs text-gray-400 mb-0.5">Beschikbaarheid</p><p class="font-medium">{{ $ch->availability ?? '–' }}</p></div>
        </div>
    </div>
    @endif

</div>
@endsection
