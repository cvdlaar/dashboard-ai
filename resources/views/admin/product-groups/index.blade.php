@extends('layouts.app')

@section('title', 'Productgroepen')

@section('header-actions')
    <a href="{{ route('admin.product-groups.create') }}" class="btn-primary text-sm">
        + Nieuwe groep
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

    {{-- Info banner --}}
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
        <strong>Hoe werkt het?</strong> Maak productgroepen aan (bijv. "Palletwagens", "Orderverzameltrucks") en koppel hier specialisten aan.
        Elke URL in de paginalijst kan worden toegewezen aan een groep. Specialisten zien automatisch alleen de pagina's van hun groepen.
        De admin heeft altijd toegang tot alle pagina's.
    </div>

    @if($groups->isEmpty())
        <div class="card text-center py-12">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="text-gray-500 text-sm">Nog geen productgroepen voor {{ $currentSite->name }}</p>
            <a href="{{ route('admin.product-groups.create') }}" class="btn-primary mt-4 inline-block text-sm">
                Eerste groep aanmaken
            </a>
        </div>
    @else
        <div class="card overflow-hidden p-0">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Groep</th>
                        <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Specialisten</th>
                        <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide px-6 py-3">Pagina's</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($groups as $group)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $group->name }}</p>
                            @if($group->description)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $group->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @forelse($group->users as $user)
                                <span class="inline-flex items-center bg-blue-50 text-blue-700 text-xs font-medium px-2 py-0.5 rounded-full mr-1 mb-1">
                                    {{ $user->name }}
                                </span>
                            @empty
                                <span class="text-gray-400 text-xs italic">Geen specialisten</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold text-gray-900">{{ number_format($group->pages_count) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('admin.product-groups.edit', $group) }}"
                               class="text-xs font-medium text-brand-blue hover:underline">Bewerken</a>
                            <form method="POST" action="{{ route('admin.product-groups.destroy', $group) }}"
                                  class="inline" onsubmit="return confirm('Productgroep verwijderen? URL\'s worden losgekoppeld.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:underline">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
