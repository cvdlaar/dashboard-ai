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
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->name }}</option>
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
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pagina</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Score</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Positie</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">CWV</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Marge</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioriteit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($query as $i => $page)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ route('pages.show', $page) }}" class="font-medium text-gray-900 hover:text-brand-blue truncate block max-w-xs">{{ $page->title ?? $page->path }}</a>
                        <p class="text-xs text-gray-400">{{ $page->site->name }}</p>
                    </td>
                    <td class="px-6 py-3 font-bold text-brand-orange">{{ $page->priority_score }}</td>
                    <td class="px-6 py-3">{{ $page->latestGscMetric ? number_format($page->latestGscMetric->position, 1) : '–' }}</td>
                    <td class="px-6 py-3">
                        @if($page->latestCwvMetric)
                            <span class="{{ $page->latestCwvMetric->performance_score >= 90 ? 'badge-good' : ($page->latestCwvMetric->performance_score >= 50 ? 'badge-warning' : 'badge-poor') }}">{{ $page->latestCwvMetric->performance_score }}</span>
                        @else <span class="text-gray-300">–</span> @endif
                    </td>
                    <td class="px-6 py-3">{{ $page->channableData?->margin ? number_format($page->channableData->margin * 100, 0).'%' : '–' }}</td>
                    <td class="px-6 py-3">
                        <span class="{{ $page->priority_label === 'Hoog' ? 'badge-poor' : ($page->priority_label === 'Middel' ? 'badge-warning' : 'badge-good') }}">{{ $page->priority_label }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
