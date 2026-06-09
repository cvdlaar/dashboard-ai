@extends('layouts.app')

@section('title', 'Productgroepen')

@section('header-actions')
    <a href="{{ route('admin.product-groups.create') }}"
       class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg bg-brand-blue text-white hover:opacity-90 transition-opacity">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nieuwe groep
    </a>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Uitleg + site tabs naast elkaar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Productgroepen voor {{ $currentSite->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">Koppel specialisten aan groepen — zij zien alleen de pagina's van hun groepen.</p>
        </div>

        @if($sites->count() > 1)
        <div class="flex space-x-1 bg-gray-100 p-1 rounded-xl flex-shrink-0">
            @foreach($sites as $s)
            <a href="?site_id={{ $s->id }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                   {{ $currentSite->id === $s->id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                {{ $s->name }}
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Info over specialisten --}}
    @if($specialists->isEmpty())
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800 flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-medium">Geen specialisten aangemaakt</p>
                <p class="mt-0.5">Ga naar <a href="{{ route('admin.users.index') }}" class="underline">Gebruikers</a> om content specialisten toe te voegen met de rol <em>specialist</em>.</p>
            </div>
        </div>
    @else
        {{-- Beschikbare specialisten als chips --}}
        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">Specialisten:</span>
            @foreach($specialists as $user)
                <span class="inline-flex items-center bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-1 rounded-full">
                    <span class="w-5 h-5 rounded-full bg-brand-blue text-white text-xs flex items-center justify-center mr-1.5 flex-shrink-0 font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                    {{ $user->name }}
                </span>
            @endforeach
        </div>
    @endif

    @if($groups->isEmpty())
        {{-- Lege staat --}}
        <div class="card text-center py-16">
            <div class="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <p class="text-gray-900 font-medium">Nog geen productgroepen</p>
            <p class="text-sm text-gray-500 mt-1 mb-6 max-w-sm mx-auto">
                Maak groepen aan per productcategorie en koppel een specialist. Zo weet elke specialist welke pagina's hij beheert.
            </p>
            <a href="{{ route('admin.product-groups.create') }}"
               class="inline-flex items-center text-sm font-medium px-5 py-2.5 rounded-lg bg-brand-blue text-white hover:opacity-90 transition-opacity">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Eerste productgroep aanmaken
            </a>
        </div>
    @else
        {{-- Groepen tabel --}}
        <div class="card overflow-hidden p-0">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3 w-1/3">Productgroep</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Specialisten</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Pagina's</th>
                        <th class="px-6 py-3 w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($groups as $group)
                    <tr class="hover:bg-gray-50 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $group->name }}</p>
                            @if($group->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $group->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @forelse($group->users as $user)
                                <span class="inline-flex items-center bg-brand-blue/10 text-brand-blue text-xs font-medium px-2 py-0.5 rounded-full mr-1 mb-1">
                                    <span class="w-4 h-4 rounded-full bg-brand-blue text-white text-xs flex items-center justify-center mr-1 font-bold flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    {{ $user->name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400 italic">Geen specialist gekoppeld</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold text-gray-900">{{ number_format($group->pages_count) }}</span>
                            <span class="text-gray-400 text-xs ml-1">URL's</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.product-groups.edit', $group) }}"
                                   class="text-xs font-medium text-brand-blue hover:underline">Bewerken</a>
                                <form method="POST" action="{{ route('admin.product-groups.destroy', $group) }}"
                                      onsubmit="return confirm('Productgroep &quot;{{ $group->name }}&quot; verwijderen? URL\'s worden losgekoppeld.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-500 hover:underline">
                                        Verwijderen
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
                {{ $groups->count() }} {{ Str::plural('productgroep', $groups->count()) }} · {{ $groups->sum('pages_count') }} URL's totaal
            </div>
        </div>
    @endif

</div>
@endsection
