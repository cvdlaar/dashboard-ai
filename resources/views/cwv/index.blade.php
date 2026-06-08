@extends('layouts.app')
@section('title', 'Core Web Vitals')
@section('content')
<div class="space-y-6">
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
                <label class="block text-xs font-medium text-gray-500 mb-1">Apparaat</label>
                <select name="strategy" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="mobile" @selected($strategy === 'mobile')>Mobiel</option>
                    <option value="desktop" @selected($strategy === 'desktop')>Desktop</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Filter</label>
                <select name="filter" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="">Alle pagina's</option>
                    <option value="poor" @selected(request('filter') === 'poor')>Alleen slecht</option>
                </select>
            </div>
            <button type="submit" class="btn-primary text-sm py-2">Filteren</button>
        </form>
    </div>

    @if($avgScores && $avgScores->perf)
    <div class="grid grid-cols-4 gap-4">
        @foreach(['perf' => ['Performance', ''], 'lcp' => ['LCP', 's'], 'cls' => ['CLS', ''], 'inp' => ['INP', 'ms']] as $key => [$label, $unit])
        <div class="card text-center">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $label }}</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $unit === 'ms' ? number_format($avgScores->$key) : number_format($avgScores->$key, $unit === 's' ? 2 : ($unit === '' && $key === 'cls' ? 3 : 0)) }}{{ $unit }}</p>
            <p class="text-xs text-gray-400 mt-1">Gemiddeld</p>
        </div>
        @endforeach
    </div>
    @endif

    <div class="card p-0 overflow-hidden">
        @if($metrics->isEmpty())
            <div class="py-16 text-center">
                <p class="text-gray-400 text-sm">Nog geen CWV data — voer eerst een scan uit.</p>
                <p class="text-gray-400 text-xs mt-1">Gebruik: <code class="bg-gray-100 px-1 rounded">php artisan scan:cwv</code></p>
            </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pagina</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Score</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">LCP</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">CLS</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">INP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($metrics as $m)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <p class="font-medium text-gray-900 truncate max-w-sm">{{ $m->page->title ?? $m->page->path }}</p>
                        <p class="text-xs text-gray-400">{{ $m->page->site->name }}</p>
                    </td>
                    <td class="px-4 py-3 text-center font-bold {{ $m->performance_score >= 90 ? 'text-green-600' : ($m->performance_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $m->performance_score }}</td>
                    <td class="px-4 py-3 text-center"><span class="badge-{{ $m->lcp_rating ?? 'good' }}">{{ number_format($m->lcp, 2) }}s</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-{{ $m->cls_rating ?? 'good' }}">{{ $m->cls }}</span></td>
                    <td class="px-4 py-3 text-center"><span class="badge-{{ $m->inp_rating ?? 'good' }}">{{ number_format($m->inp) }}ms</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">{{ $metrics->links() }}</div>
        @endif
    </div>
</div>
@endsection
