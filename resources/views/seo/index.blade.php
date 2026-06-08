@extends('layouts.app')
@section('title', 'SEO & Search Console')
@section('content')
<div class="space-y-6">
    <div class="card py-4">
        <form method="GET" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Website</label>
                <select name="site" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle websites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm py-2">Filteren</button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <h2 class="font-semibold text-gray-900 mb-4">Top zoekwoorden (28 dagen)</h2>
            @if($topQueries->isEmpty())
                <p class="text-sm text-gray-400 py-8 text-center">Nog geen Search Console data gekoppeld.</p>
            @else
            <table class="w-full text-sm">
                <thead><tr class="border-b border-gray-100">
                    <th class="text-left py-2 text-xs text-gray-500">Zoekwoord</th>
                    <th class="text-right py-2 text-xs text-gray-500">Impressies</th>
                    <th class="text-right py-2 text-xs text-gray-500">Clicks</th>
                    <th class="text-right py-2 text-xs text-gray-500">Positie</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($topQueries as $q)
                    <tr>
                        <td class="py-2 text-gray-900">{{ $q->query }}</td>
                        <td class="py-2 text-right text-gray-600">{{ number_format($q->total_impressions) }}</td>
                        <td class="py-2 text-right text-gray-600">{{ number_format($q->total_clicks) }}</td>
                        <td class="py-2 text-right font-medium">{{ number_format($q->avg_position, 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <div class="card">
            <h2 class="font-semibold text-gray-900 mb-4">Pagina's op positie</h2>
            @if($pages->isEmpty())
                <p class="text-sm text-gray-400 py-8 text-center">Nog geen data beschikbaar.</p>
            @else
            <div class="space-y-2">
                @foreach($pages->take(15) as $page)
                @php $gsc = $page->latestGscMetric; @endphp
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div class="flex-1 min-w-0 mr-3">
                        <p class="text-sm text-gray-900 truncate">{{ $page->title ?? $page->path }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs flex-shrink-0">
                        <span class="text-gray-500">{{ number_format($gsc->impressions) }} imp.</span>
                        <span class="font-semibold {{ $gsc->position <= 3 ? 'text-green-600' : ($gsc->position <= 10 ? 'text-yellow-600' : 'text-red-500') }}">pos. {{ number_format($gsc->position, 1) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
