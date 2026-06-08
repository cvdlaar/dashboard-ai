@extends('layouts.app')

@section('title', 'Nieuwe productgroep')

@section('content')
<div class="max-w-2xl">

    <div class="mb-6">
        <a href="{{ route('admin.product-groups.index') }}" class="text-sm text-brand-blue hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Terug naar productgroepen
        </a>
    </div>

    <div class="card">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Nieuwe productgroep</h2>

        <form method="POST" action="{{ route('admin.product-groups.store') }}">
            @csrf

            <input type="hidden" name="site_id" value="{{ $currentSite->id }}">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <p class="text-sm text-gray-600 px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    {{ $currentSite->name }} — {{ $currentSite->domain }}
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Naam <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="bijv. Palletwagens, Orderverzameltrucks"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Omschrijving <span class="text-gray-400 font-normal">(optioneel)</span></label>
                <textarea name="description" rows="2"
                          placeholder="Korte beschrijving van deze productgroep"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">{{ old('description') }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Specialisten koppelen</label>
                @forelse($specialists as $user)
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                               {{ in_array($user->id, old('user_ids', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-2.5">
                        <span class="text-sm text-gray-700">{{ $user->name }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $user->email }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-400 italic">Geen specialisten beschikbaar — maak eerst gebruikers aan met de rol 'specialist'.</p>
                @endforelse
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route('admin.product-groups.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    Annuleren
                </a>
                <button type="submit" class="btn-primary">Aanmaken</button>
            </div>
        </form>
    </div>

</div>
@endsection
