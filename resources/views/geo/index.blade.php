@extends('layouts.app')
@section('title', 'GEO / AI-zichtbaarheid')
@section('content')
<div class="space-y-6">

    <div class="card bg-blue-50 border-blue-200">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-brand-blue mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900">Generative Engine Optimization (GEO)</p>
                <p class="text-sm text-blue-700 mt-1">Dit overzicht laat zien welke pagina's klaar zijn om door AI-zoekmachines (ChatGPT, Perplexity, Google AI Overviews) te worden gebruikt. Goede GEO-score = gestructureerde data + duidelijke meta + H1.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="card text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">JSON-LD aanwezig</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['with_json_ld'] }}</p>
            <p class="text-xs text-gray-400 mt-1">van {{ $stats['total'] }} pagina's</p>
        </div>
        <div class="card text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Meta description</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['with_meta'] }}</p>
            <p class="text-xs text-gray-400 mt-1">van {{ $stats['total'] }} pagina's</p>
        </div>
        <div class="card text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">H1 aanwezig</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['with_h1'] }}</p>
            <p class="text-xs text-gray-400 mt-1">van {{ $stats['total'] }} pagina's</p>
        </div>
    </div>

    <div class="card py-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Website</label>
                <select name="site" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle websites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Filter</label>
                <select name="filter" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle pagina's</option>
                    <option value="no_jsonld" @selected(request('filter') === 'no_jsonld')>Geen JSON-LD</option>
                    <option value="no_meta" @selected(request('filter') === 'no_meta')>Geen meta description</option>
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm py-2">Filteren</button>
        </form>
    </div>

    <div class="card p-0 overflow-hidden">
        @if($pages->isEmpty())
            <div class="py-16 text-center"><p class="text-gray-400 text-sm">Geen pagina's — importeer eerst de sitemap.</p></div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pagina</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">JSON-LD</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Meta desc.</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">H1</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($pages as $page)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <p class="font-medium text-gray-900 truncate max-w-sm">{{ $page->title ?? $page->path }}</p>
                        <p class="text-xs text-gray-400">{{ $page->site->name }}</p>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($page->has_json_ld) <span class="badge-good">✓</span> @else <span class="badge-poor">✗</span> @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($page->meta_description) <span class="badge-good">✓</span> @else <span class="badge-poor">✗</span> @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($page->h1) <span class="badge-good">✓</span> @else <span class="badge-poor">✗</span> @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">{{ $pages->links() }}</div>
        @endif
    </div>
</div>
@endsection
