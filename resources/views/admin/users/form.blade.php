@extends('layouts.app')
@section('title', $user->exists ? 'Gebruiker bewerken' : 'Gebruiker toevoegen')
@section('content')
<div class="max-w-md">
    <div class="mb-6"><a href="{{ route('admin.users.index') }}" class="text-sm text-brand-blue hover:underline">← Terug</a></div>
    <div class="card">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf @if($user->exists) @method('PUT') @endif
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord {{ $user->exists ? '(leeg laten = niet wijzigen)' : '' }}</label>
                    <input type="password" name="password" {{ !$user->exists ? 'required' : '' }} class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord bevestigen</label>
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                    <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                        <option value="specialist" @selected(old('role', $user->exists ? $user->getRoleNames()->first() : 'specialist') === 'specialist')>Specialist</option>
                        <option value="admin" @selected(old('role', $user->exists ? $user->getRoleNames()->first() : '') === 'admin')>Admin</option>
                    </select>
                </div>
            </div>
            @if($errors->any())
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="flex justify-between mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500">Annuleren</a>
                <button type="submit" class="btn-primary">Opslaan</button>
            </div>
        </form>
    </div>
</div>
@endsection
