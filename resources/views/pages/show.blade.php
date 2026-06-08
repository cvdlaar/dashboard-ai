@extends('layouts.app')
@section('title', $page->title ?? $page->path)
@section('content')
<div class="space-y-6 max-w-4xl">
    <div class="mb-2">
        <a href="{{ route('pages.index') }}" class="text-sm text-brand-blue hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Terug
        </a>
    </div>

    <div class="card">
        <h2 class="font-semibold text-gray-900 mb-1">{{ $page->title ?? '(geen titel)' }}</h2>
        <a href="{{ $page->url }}" target="_blank" class="text-sm text-brand-blue hover:underline break-all">{{ $page->url }}</a>
        <div class="flex flex-wrap gap-2 mt-3">
            <span class="badge-good">{{ ucfirst($page->type) }}</span>
            <span class="badge-good">{{ strtoupper($page->language) }}</span>
            @if($page->has_json_ld) <span class="badge-good">JSON-LD</span> @endif
            @if($page->categoryOwner) <span class="inline-flex bg-blue-100 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ $page->categoryOwner->name }}</span> @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- CWV --}}
        <div class="card">
            <h3 class="font-semibold text-gray-900 mb-4">Core Web Vitals</h3>
            @if($page->latestCwvMetric)
            @php $cwv = $page->latestCwvMetric; @endphp
            <div class="flex items-center mb-4">
                <div class="text-5xl font-bold {{ $cwv->performance_score >= 90 ? 'text-green-600' : ($cwv->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }} mr-4">{{ $cwv->performance_score }}</div>
                <div class="text-sm text-gray-500">Performance score<br>(mobiel)</div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">LCP</span><span class="badge-{{ $cwv->lcp_rating ?? 'good' }}">{{ number_format($cwv->lcp, 2) }}s</span></div>
                <div class="flex justify-between"><span class="text-gray-600">CLS</span><span class="badge-{{ $cwv->cls_rating ?? 'good' }}">{{ $cwv->cls }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">INP</span><span class="badge-{{ $cwv->inp_rating ?? 'good' }}">{{ number_format($cwv->inp) }}ms</span></div>
            </div>
            @else
            <p class="text-sm text-gray-400 py-4 text-center">Nog geen CWV data — voer een scan uit.</p>
            @endif
        </div>

        {{-- GSC --}}
        <div class="card">
            <h3 class="font-semibold text-gray-900 mb-4">Search Console (28 dagen)</h3>
            @if($page->latestGscMetric)
            @php $gsc = $page->latestGscMetric; @endphp
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Positie</span><span class="font-semibold">{{ number_format($gsc->position, 1) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Impressies</span><span class="font-semibold">{{ number_format($gsc->impressions) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Clicks</span><span class="font-semibold">{{ number_format($gsc->clicks) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">CTR</span><span class="font-semibold">{{ number_format($gsc->ctr * 100, 1) }}%</span></div>
            </div>
            @else
            <p class="text-sm text-gray-400 py-4 text-center">Nog geen GSC data.</p>
            @endif
        </div>

        {{-- Channable --}}
        @if($page->channableData)
        <div class="card">
            <h3 class="font-semibold text-gray-900 mb-4">Product (Channable)</h3>
            @php $ch = $page->channableData; @endphp
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-600">Prijs</span><span class="font-semibold">€ {{ number_format($ch->price, 2) }}</span></div>
                @if($ch->margin)<div class="flex justify-between"><span class="text-gray-600">Marge</span><span class="font-semibold text-green-600">{{ number_format($ch->margin * 100, 1) }}%</span></div>@endif
                <div class="flex justify-between"><span class="text-gray-600">Beschikbaarheid</span><span>{{ $ch->availability ?? '–' }}</span></div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
