@extends('layouts.app')
@section('title', 'Gebruikers')
@section('header-actions')
<a href="{{ route('admin.users.create') }}" class="btn-primary text-sm py-1.5">+ Gebruiker toevoegen</a>
@endsection
@section('content')
<div class="card p-0 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Naam</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Rol</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3">
                    <p class="font-medium text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                </td>
                <td class="px-6 py-3">
                    <span class="{{ $user->hasRole('admin') ? 'bg-brand-blue text-white' : 'bg-gray-100 text-gray-700' }} text-xs font-medium px-2 py-0.5 rounded-full">
                        {{ $user->hasRole('admin') ? 'Admin' : 'Specialist' }}
                    </span>
                </td>
                <td class="px-6 py-3 text-right space-x-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-brand-blue hover:underline">Bewerken</a>
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Gebruiker verwijderen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Verwijderen</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
