@extends('layouts.app')
@section('title', 'Websites')
@section('header-actions')
<a href="{{ route('admin.sites.create') }}" class="btn-primary text-sm py-1.5">+ Website toevoegen</a>
@endsection
@section('content')
<div class="card p-0 overflow-hidden">
    @if($sites->isEmpty())
        <div class="py-16 text-center"><p class="text-gray-400 text-sm">Nog geen websites.</p></div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Website</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Talen</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Pagina's</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($sites as $site)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3">
                    <p class="font-medium text-gray-900">{{ $site->name }}</p>
                    <p class="text-xs text-gray-400">{{ $site->domain }}</p>
                </td>
                <td class="px-6 py-3">{{ implode(', ', $site->languages ?? ['nl']) }}</td>
                <td class="px-6 py-3 text-right font-medium">{{ number_format($site->pages_count) }}</td>
                <td class="px-6 py-3 text-right space-x-3">
                    <a href="{{ route('admin.sites.edit', $site) }}" class="text-xs text-brand-blue hover:underline">Bewerken</a>
                    <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" class="inline" onsubmit="return confirm('Weet je het zeker?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Verwijderen</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
