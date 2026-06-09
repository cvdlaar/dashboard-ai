@extends('layouts.app')
@section('title', "Pagina's")
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
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select name="type" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle types</option>
                    <option value="product"  @selected(request('type') === 'product')>Product</option>
                    <option value="category" @selected(request('type') === 'category')>Categorie</option>
                    <option value="content"  @selected(request('type') === 'content')>Content</option>
                </select>
            </div>
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Zoeken</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="URL of paginatitel..."
                    class="w-full text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
            </div>
            <button type="submit" class="btn-primary text-sm py-2">Filteren</button>
        </form>
    </div>

    <div class="card p-0 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $query->total() }} pagina's gevonden</p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>Goed</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Verbetering</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>Slecht</span>
            </div>
        </div>

        @if($query->isEmpty())
            <div class="py-16 text-center">
                <p class="text-gray-400 text-sm">Geen pagina's — voer eerst een sitemap import uit.</p>
                <p class="text-gray-400 text-xs mt-1">Gebruik: <code class="bg-gray-100 px-1 rounded">php artisan import:sitemap</code></p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pagina</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Pos.</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">GSC CTR</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">CWV</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">GEO</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Prioriteit</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($query as $page)
                    @php
                        $gsc  = $page->latestGscMetric;
                        $cwv  = $page->latestCwvMetric;
                        $geo  = $page->latestGeoMetric;
                        $prio = $page->priority_score;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-900 truncate max-w-xs">{{ $page->title ?? $page->path }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-400 truncate max-w-xs">{{ $page->path }}</span>
                                <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0 rounded capitalize">{{ $page->type }}</span>
                            </div>
                        </td>

                        {{-- GSC Positie --}}
                        <td class="px-4 py-3 text-center">
                            @if($gsc && $gsc->position)
                                <span class="text-sm font-semibold
                                    {{ $gsc->position <= 3 ? 'text-green-600' : ($gsc->position <= 10 ? 'text-amber-600' : 'text-gray-500') }}">
                                    {{ number_format($gsc->position, 0) }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">–</span>
                            @endif
                        </td>

                        {{-- GSC CTR --}}
                        <td class="px-4 py-3 text-center">
                            @if($gsc && $gsc->ctr !== null)
                                @php $expectedCtr = ($gsc->position ?? 99) <= 3 ? 0.10 : 0.03; @endphp
                                <span class="text-xs font-medium {{ $gsc->ctr >= $expectedCtr ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ number_format($gsc->ctr * 100, 1) }}%
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">–</span>
                            @endif
                        </td>

                        {{-- CWV --}}
                        <td class="px-4 py-3 text-center">
                            @if($cwv && $cwv->performance_score !== null)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                    {{ $cwv->performance_score >= 90 ? 'bg-green-100 text-green-700' : ($cwv->performance_score >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                    {{ $cwv->performance_score }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">–</span>
                            @endif
                        </td>

                        {{-- GEO --}}
                        <td class="px-4 py-3 text-center">
                            @if($geo)
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold
                                    {{ $geo->geo_score >= 75 ? 'bg-green-100 text-green-700' : ($geo->geo_score >= 45 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                    {{ $geo->geo_score }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">–</span>
                            @endif
                        </td>

                        {{-- Prioriteit --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $prio >= 70 ? 'bg-red-400' : ($prio >= 40 ? 'bg-amber-400' : 'bg-green-400') }}"
                                         style="width: {{ $prio }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-600 w-6">{{ $prio }}</span>
                            </div>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('pages.show', $page) }}" class="text-xs text-brand-blue hover:underline whitespace-nowrap">Details →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $query->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
