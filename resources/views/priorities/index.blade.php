@extends('layouts.app')
@section('title', 'Prioriteiten')
@section('content')
<div class="space-y-4">
    {{-- Filters --}}
    <div class="card py-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Website</label>
                <select name="site" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle websites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(request('site', $siteId) == $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth()->user()->hasRole('admin'))
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Eigenaar</label>
                <select name="owner" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle eigenaren</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected(request('owner') == $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn-primary text-sm py-2">Filteren</button>
        </form>
    </div>

    @if($query->isEmpty())
    <div class="card py-16 text-center">
        <p class="text-gray-400 text-sm">Nog geen pagina's — importeer eerst de sitemap.</p>
    </div>
    @else
    <div class="card p-0 overflow-hidden">
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-xs text-gray-400">Gesorteerd op prioriteit (hoog naar laag) · Score = marge + SEO-kans + CWV + GEO-gap</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-8">#</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pagina</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Prio</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Pos.</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">CTR</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">CWV</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">GEO</th>
                        <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Marge</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($query as $i => $page)
                    @php
                        $gsc  = $page->latestGscMetric;
                        $cwv  = $page->latestCwvMetric;
                        $geo  = $page->latestGeoMetric;
                        $prio = $page->priority_score;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>

                        <td class="px-6 py-3">
                            <a href="{{ route('pages.show', $page) }}"
                               class="font-medium text-gray-900 hover:text-brand-blue truncate block max-w-xs">
                                {{ $page->title ?? $page->path }}
                            </a>
                            <p class="text-xs text-gray-400">{{ $page->site->name }} · {{ $page->categoryOwner?->name ?? '–' }}</p>
                        </td>

                        {{-- Prioriteit score + label --}}
                        <td class="px-3 py-3 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-sm font-bold {{ $prio >= 70 ? 'text-red-600' : ($prio >= 40 ? 'text-amber-600' : 'text-green-600') }}">
                                    {{ $prio }}
                                </span>
                                <span class="text-xs {{ $page->priority_label === 'Hoog' ? 'text-red-500' : ($page->priority_label === 'Middel' ? 'text-amber-500' : 'text-green-500') }}">
                                    {{ $page->priority_label }}
                                </span>
                            </div>
                        </td>

                        {{-- GSC positie --}}
                        <td class="px-3 py-3 text-center">
                            @if($gsc && $gsc->position)
                                <span class="text-sm font-semibold
                                    {{ $gsc->position <= 3 ? 'text-green-600' : ($gsc->position <= 10 ? 'text-amber-600' : 'text-gray-500') }}">
                                    {{ number_format($gsc->position, 0) }}
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>

                        {{-- CTR --}}
                        <td class="px-3 py-3 text-center">
                            @if($gsc && $gsc->ctr !== null)
                                @php $exp = ($gsc->position ?? 99) <= 3 ? 0.10 : 0.03; @endphp
                                <span class="text-xs font-medium {{ $gsc->ctr >= $exp ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ number_format($gsc->ctr * 100, 1) }}%
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>

                        {{-- CWV --}}
                        <td class="px-3 py-3 text-center">
                            @if($cwv && $cwv->performance_score !== null)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                    {{ $cwv->performance_score >= 90 ? 'bg-green-100 text-green-700' : ($cwv->performance_score >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                    {{ $cwv->performance_score }}
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>

                        {{-- GEO --}}
                        <td class="px-3 py-3 text-center">
                            @if($geo)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                    {{ $geo->geo_score >= 75 ? 'bg-green-100 text-green-700' : ($geo->geo_score >= 45 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                    {{ $geo->geo_score }}
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>

                        {{-- Marge --}}
                        <td class="px-3 py-3 text-center">
                            @if($page->channableData?->margin)
                                <span class="text-xs font-medium text-green-600">
                                    {{ number_format($page->channableData->margin * 100, 0) }}%
                                </span>
                            @else
                                <span class="text-gray-300">–</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('pages.show', $page) }}" class="text-xs text-brand-blue hover:underline">Details →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
