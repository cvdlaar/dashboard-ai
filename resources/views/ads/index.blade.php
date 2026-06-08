@extends('layouts.app')
@section('title', 'Advertenties')
@section('content')
<div class="space-y-6">
    <div class="card py-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Platform</label>
                <select name="platform" class="text-sm border-gray-300 rounded-lg focus:ring-brand-blue focus:border-brand-blue">
                    <option value="google" @selected($platform === 'google')>Google Ads</option>
                    <option value="bing" @selected($platform === 'bing')>Microsoft Ads</option>
                </select>
            </div>
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

    @if($totals && $totals->total_cost > 0)
    <div class="grid grid-cols-4 gap-4">
        <div class="card"><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Spend (28d)</p><p class="text-3xl font-bold text-gray-900 mt-1">€ {{ number_format($totals->total_cost, 0, ',', '.') }}</p></div>
        <div class="card"><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</p><p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totals->total_clicks) }}</p></div>
        <div class="card"><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Conversies</p><p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totals->total_conversions) }}</p></div>
        <div class="card"><p class="text-xs font-medium text-gray-500 uppercase tracking-wider">ROAS</p><p class="text-3xl font-bold text-gray-900 mt-1">{{ $totals->total_cost > 0 ? number_format($totals->total_value / $totals->total_cost, 2) : '–' }}x</p></div>
    </div>
    @endif

    <div class="card p-0 overflow-hidden">
        @if($metrics->isEmpty())
            <div class="py-16 text-center"><p class="text-gray-400 text-sm">Nog geen advertentiedata — koppel {{ $platform === 'google' ? 'Google Ads' : 'Microsoft Ads' }} via Koppelingen.</p></div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pagina</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Spend</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Clicks</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Conv.</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">ROAS</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($metrics as $m)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3"><p class="font-medium text-gray-900 truncate max-w-sm">{{ $m->page->title ?? $m->page->path }}</p></td>
                    <td class="px-4 py-3 text-right font-medium">€ {{ number_format($m->cost, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($m->clicks) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($m->conversions) }}</td>
                    <td class="px-4 py-3 text-right">{{ $m->cost > 0 ? number_format($m->conversion_value / $m->cost, 2) : '–' }}x</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">{{ $metrics->links() }}</div>
        @endif
    </div>
</div>
@endsection
