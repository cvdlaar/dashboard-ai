@extends('layouts.app')
@section('title', 'llms.txt generator')
@section('content')
<div class="space-y-6 max-w-4xl">

    <div>
        <a href="{{ route('geo.index', ['site_id' => $siteId]) }}" class="text-sm text-brand-blue hover:underline flex items-center mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Terug naar GEO dashboard
        </a>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">llms.txt generator</h1>
                <p class="text-sm text-gray-500 mt-0.5">Gegenereerd voor <strong>{{ $site->name }}</strong> op basis van de pagina-database</p>
            </div>
            {{-- Site tabs --}}
            @if($sites->count() > 1)
            <div class="flex space-x-1 bg-gray-100 p-1 rounded-xl">
                @foreach($sites as $s)
                <a href="?site_id={{ $s->id }}"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                       {{ $site->id === $s->id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ $s->name }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Uitleg --}}
    <div class="card bg-blue-50 border-blue-200">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Wat is llms.txt?</p>
                <p>Een leesbaar Markdown-bestand op <code class="bg-blue-100 px-1 rounded">https://{{ $site->domain }}/llms.txt</code> dat AI-modellen vertelt welke pagina's op jouw site bestaan en wat ze bevatten. LLMs kunnen dit gebruiken om jouw site beter te begrijpen en te citeren.</p>
                <p class="mt-1">Kopieer de inhoud hieronder en zet het bestand handmatig op je server, of stuur het door naar je webmaster.</p>
            </div>
        </div>
    </div>

    {{-- Acties --}}
    <div class="flex items-center gap-3">
        <button onclick="copyContent()"
            class="btn-primary text-sm py-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span id="copy-label">Kopieer naar klembord</span>
        </button>

        <a href="{{ route('geo.llms-txt', ['site_id' => $siteId, 'download' => 1]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download llms.txt
        </a>

        <span class="text-xs text-gray-400">
            {{ substr_count($content, "\n- ") }} pagina's opgenomen
        </span>
    </div>

    {{-- Preview --}}
    <div class="card p-0 overflow-hidden">
        <div class="px-4 py-2.5 bg-gray-800 flex items-center justify-between">
            <span class="text-xs text-gray-400 font-mono">llms.txt — {{ $site->domain }}</span>
            <span class="text-xs text-gray-500">Markdown</span>
        </div>
        <pre id="llms-content"
             class="text-xs leading-relaxed p-5 overflow-x-auto bg-gray-900 text-gray-100 max-h-[60vh] overflow-y-auto font-mono whitespace-pre-wrap">{{ $content }}</pre>
    </div>

</div>

<script>
function copyContent() {
    const text = document.getElementById('llms-content').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const label = document.getElementById('copy-label');
        label.textContent = 'Gekopieerd!';
        setTimeout(() => label.textContent = 'Kopieer naar klembord', 2000);
    });
}
</script>
@endsection
