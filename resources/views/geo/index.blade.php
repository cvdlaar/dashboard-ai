@extends('layouts.app')
@section('title', 'GEO / AI-zichtbaarheid')

@section('content')
<div class="space-y-6">

    {{-- Site tabs --}}
    @if($sites->count() > 1)
    <div class="flex space-x-1 bg-gray-100 p-1 rounded-xl w-fit">
        @foreach($sites as $s)
        <a href="?site_id={{ $s->id }}"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
               {{ $currentSite->id === $s->id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
            {{ $s->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- LLM-bot status --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">LLM-bots & indexeerbaarheid</h2>
            @if($siteGeoCheck)
                <span class="text-xs text-gray-400">Gecontroleerd {{ $siteGeoCheck->checked_at->diffForHumans() }}</span>
            @else
                <span class="text-xs text-amber-600 font-medium">Nog niet gecheckt</span>
            @endif
        </div>

        @if($siteGeoCheck)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-5">
            @foreach($knownBots as $bot => $info)
                @php $status = $siteGeoCheck->getBotStatus($bot); @endphp
                <div class="border rounded-lg p-3 text-center
                    {{ in_array($status, ['blocked','blocked-by-wildcard']) ? 'border-red-200 bg-red-50' : ($status === 'allowed' ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50') }}">
                    <p class="text-xs font-medium text-gray-800 leading-tight mb-1.5">{{ $info['label'] }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ in_array($status, ['blocked','blocked-by-wildcard'])
                            ? 'bg-red-100 text-red-700'
                            : ($status === 'allowed' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500') }}">
                        {{ match($status) {
                            'allowed'             => 'Toegestaan',
                            'blocked'             => 'Geblokkeerd',
                            'blocked-by-wildcard' => 'Geblokkeerd (*)',
                            default               => 'Niet vermeld',
                        } }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-6 pt-4 border-t border-gray-100">
            <div class="flex items-center text-sm {{ $siteGeoCheck->has_llms_txt ? 'text-green-700' : 'text-amber-600' }}">
                <svg class="w-4 h-4 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($siteGeoCheck->has_llms_txt)
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @endif
                </svg>
                <span class="font-medium">llms.txt</span>
                <span class="ml-1 text-xs opacity-75">{{ $siteGeoCheck->has_llms_txt ? 'aanwezig' : 'ontbreekt' }}</span>
            </div>

            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span class="font-medium text-gray-700">Mention share:</span>
                @foreach(['reddit_url' => 'Reddit', 'wikipedia_url' => 'Wikipedia', 'youtube_url' => 'YouTube'] as $field => $label)
                    @if($siteGeoCheck->$field)
                        <a href="{{ $siteGeoCheck->$field }}" target="_blank" class="text-brand-blue hover:underline">{{ $label }} ✓</a>
                    @else
                        <span class="text-gray-400">{{ $label }} —</span>
                    @endif
                @endforeach
            </div>
        </div>
        @else
        <div class="py-6 text-center text-sm text-gray-400">
            Voer eerst de scanner uit:
            <code class="ml-1 bg-gray-100 px-2 py-1 rounded text-xs">php artisan scan:geo --site={{ $currentSite->domain }}</code>
        </div>
        @endif
    </div>

    {{-- Score statistieken --}}
    @if($stats['total_scored'] > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card text-center">
            <p class="text-3xl font-bold text-gray-900">{{ $stats['avg_score'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Gemiddelde GEO score</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-green-600">{{ $stats['score_good'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Goed (≥ 75)</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-amber-500">{{ $stats['score_warning'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Verbetering mogelijk</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-red-500">{{ $stats['score_poor'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Aandacht nodig (&lt; 45)</p>
        </div>
    </div>

    {{-- Criteria overzicht --}}
    <div class="card">
        <h3 class="font-semibold text-gray-900 mb-1 text-sm">Criteria-overzicht <span class="font-normal text-gray-400">({{ $stats['total_scored'] }} pagina's gescand · max 100 punten)</span></h3>
        <p class="text-xs text-gray-400 mb-4">Gebaseerd op 10-stappen GEO-framework: technisch, EEAT, content architectuur, structuur & versheid</p>
        @php
            $total = max(1, $stats['total_scored']);
            $groups = [
                'Technisch & EEAT' => [
                    ['label' => 'JSON-LD schema',          'ok' => $stats['has_json_ld'],      'pts' => 5,  'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                    ['label' => 'Organisatieschema',        'ok' => $stats['has_organization'], 'pts' => 4,  'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['label' => 'Auteur/expert schema',     'ok' => $stats['has_author'],       'pts' => 3,  'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['label' => 'Open Graph',               'ok' => $stats['has_json_ld'],      'pts' => 3,  'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z'],
                ],
                'Structuur' => [
                    ['label' => 'H1 aanwezig',              'ok' => $stats['has_json_ld'],      'pts' => 3,  'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16'],
                    ['label' => 'Inhoudsopgave (ToC)',       'ok' => $stats['has_toc'],          'pts' => 3,  'icon' => 'M4 6h16M4 12h8m-8 6h16'],
                    ['label' => 'Lijsten / tabellen',        'ok' => $stats['has_list_content'], 'pts' => 4,  'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ],
                'Content architectuur' => [
                    ['label' => 'Citeerbare intro (35+ w)',  'ok' => $stats['has_citable_intro'],'pts' => 12, 'icon' => 'M4 6h16M4 12h8m-8 6h16'],
                    ['label' => 'Vraagkoppen (H2/H3)',       'ok' => $stats['has_faq'],          'pts' => 10, 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'FAQ-blok',                  'ok' => $stats['has_faq'],          'pts' => 8,  'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Vergelijkende copy',        'ok' => $stats['has_comparison'],   'pts' => 8,  'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4'],
                    ['label' => 'Externe bronlinks (EEAT)',  'ok' => $stats['has_ext_links'],    'pts' => 7,  'icon' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'],
                ],
                'Versheid' => [
                    ['label' => 'dateModified in JSON-LD',   'ok' => $stats['has_freshness'],    'pts' => 15, 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ],
            ];
        @endphp
        <div class="space-y-4">
            @foreach($groups as $groupName => $criteria)
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $groupName }}</p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                    @foreach($criteria as $c)
                    @php $pct = round($c['ok'] / $total * 100); @endphp
                    <div class="flex items-center p-2.5 bg-gray-50 rounded-lg border {{ $pct >= 60 ? 'border-gray-100' : 'border-red-100' }}">
                        <div class="w-7 h-7 rounded-md {{ $pct >= 60 ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center mr-2 flex-shrink-0">
                            <svg class="w-3.5 h-3.5 {{ $pct >= 60 ? 'text-green-600' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-800 leading-tight truncate">{{ $c['label'] }}</p>
                            <p class="text-xs text-gray-400">{{ $c['ok'] }}/{{ $total }} · {{ $c['pts'] }}pts</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="card text-center py-10">
        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <p class="text-gray-500 text-sm font-medium">Nog geen GEO scores beschikbaar</p>
        <p class="text-gray-400 text-xs mt-1">Voer de scanner uit om pagina's te beoordelen</p>
        <code class="block mt-3 text-xs bg-gray-100 rounded px-4 py-2 w-fit mx-auto text-left">
            php artisan scan:geo --site={{ $currentSite->domain }} --limit=50
        </code>
    </div>
    @endif

    {{-- Paginalijst --}}
    @if($pages->count() > 0)
    <div class="card overflow-hidden p-0">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">
                Pagina's
                <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $pages->total() }} totaal)</span>
            </h3>
            <select onchange="window.location=this.value" class="text-xs border-gray-200 rounded-lg focus:ring-brand-blue py-1.5">
                <option value="?site_id={{ $currentSite->id }}&sort=score_asc"  {{ $sort === 'score_asc'  ? 'selected' : '' }}>Laagste score eerst</option>
                <option value="?site_id={{ $currentSite->id }}&sort=score_desc" {{ $sort === 'score_desc' ? 'selected' : '' }}>Hoogste score eerst</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">URL</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3 w-20">Score</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-3 w-16">JSON-LD</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-3 w-16">Intro</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-3 w-16">Vragen</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-3 w-16">FAQ</th>
                        <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-3 w-16">Vergelijk</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Aandachtspunten</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pages as $page)
                    @php $geo = $page->latestGeoMetric; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3">
                            <a href="{{ $page->url }}" target="_blank"
                               class="text-xs text-brand-blue hover:underline max-w-xs block truncate">
                                {{ $page->path ?: $page->url }}
                            </a>
                            <span class="text-xs text-gray-400 capitalize">{{ $page->type }}</span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if($geo)
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold
                                {{ $geo->geo_score >= 75 ? 'bg-green-100 text-green-700'
                                    : ($geo->geo_score >= 45 ? 'bg-amber-100 text-amber-700'
                                    : 'bg-red-100 text-red-600') }}">
                                {{ $geo->geo_score }}
                            </span>
                            @else
                            <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>

                        @php
                            $icon = fn(bool $val) => $val
                                ? '<svg class="w-4 h-4 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                                : '<svg class="w-4 h-4 text-red-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                        @endphp

                        <td class="px-3 py-3">{!! $geo ? $icon($geo->has_json_ld) : '<span class="text-gray-300 text-xs block text-center">—</span>' !!}</td>
                        <td class="px-3 py-3">{!! $geo ? $icon($geo->has_citable_intro) : '<span class="text-gray-300 text-xs block text-center">—</span>' !!}</td>
                        <td class="px-3 py-3 text-center">
                            @if($geo)
                                <span class="text-xs font-semibold
                                    {{ $geo->question_heading_count >= 2 ? 'text-green-600' : ($geo->question_heading_count === 1 ? 'text-amber-500' : 'text-red-400') }}">
                                    {{ $geo->question_heading_count }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">{!! $geo ? $icon($geo->has_faq_block) : '<span class="text-gray-300 text-xs block text-center">—</span>' !!}</td>
                        <td class="px-3 py-3">{!! $geo ? $icon($geo->has_comparison_content) : '<span class="text-gray-300 text-xs block text-center">—</span>' !!}</td>

                        <td class="px-6 py-3">
                            @if($geo && !empty($geo->issues))
                                <div class="space-y-0.5">
                                    @foreach(array_slice($geo->issues, 0, 2) as $issue)
                                        <p class="text-xs text-amber-600 leading-snug">{{ $issue }}</p>
                                    @endforeach
                                    @if(count($geo->issues) > 2)
                                        <p class="text-xs text-gray-400">+{{ count($geo->issues) - 2 }} meer</p>
                                    @endif
                                </div>
                            @elseif($geo)
                                <span class="text-xs text-green-600">Geen aandachtspunten</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100">
            {{ $pages->links() }}
        </div>
    </div>
    @endif

</div>
@endsection
