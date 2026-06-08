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
                        <option value="{{ $site->id }}" @selected(request('site') == $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select name="type" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle types</option>
                    <option value="product" @selected(request('type') === 'product')>Product</option>
                    <option value="category" @selected(request('type') === 'category')>Categorie</option>
                    <option value="content" @selected(request('type') === 'content')>Content</option>
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

    {{-- Table --}}
    <div class="card p-0 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ $query->total() }} pagina's gevonden</p>
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
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">CWV</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioriteit</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($query as $page)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <p class="font-medium text-gray-900 truncate max-w-xs">{{ $page->title ?? $page->path }}</p>
                            <p class="text-xs text-gray-400 truncate max-w-xs">{{ $page->path }}</p>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded-full">{{ ucfirst($page->type) }}</span>
                        </td>
                        <td class="px-6 py-3">
                            @if($page->latestCwvMetric)
                                <span class="{{ $page->latestCwvMetric->performance_score >= 90 ? 'badge-good' : ($page->latestCwvMetric->performance_score >= 50 ? 'badge-warning' : 'badge-poor') }}">
                                    {{ $page->latestCwvMetric->performance_score }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">–</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @php $score = $page->priority_score; @endphp
                            <span class="{{ $score >= 70 ? 'badge-poor' : ($score >= 40 ? 'badge-warning' : 'badge-good') }}">
                                {{ $score >= 70 ? 'Hoog' : ($score >= 40 ? 'Middel' : 'Laag') }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('pages.show', $page) }}" class="text-xs text-brand-blue hover:underline">Details</a>
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
