@extends('layouts.app')
@section('title', $site->exists ? 'Website bewerken' : 'Website toevoegen')
@section('content')
<div class="max-w-xl">
    <div class="mb-6"><a href="{{ route('admin.sites.index') }}" class="text-sm text-brand-blue hover:underline">← Terug</a></div>
    <div class="card">
        <form method="POST" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}">
            @csrf @if($site->exists) @method('PUT') @endif
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                    <input type="text" name="name" value="{{ old('name', $site->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Domein</label>
                    <input type="text" name="domain" value="{{ old('domain', $site->domain) }}" placeholder="logistiekconcurrent.nl" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Sitemap URL</label>
                    <input type="url" name="sitemap_url" value="{{ old('sitemap_url', $site->sitemap_url) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Talen</label>
                    <div class="flex gap-4">
                        @foreach(['nl' => 'Nederlands', 'fr' => 'Frans', 'en' => 'Engels'] as $code => $label)
                        <label class="flex items-center"><input type="checkbox" name="languages[]" value="{{ $code }}" @checked(in_array($code, old('languages', $site->languages ?? ['nl']))) class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-2">{{ $label }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex justify-between mt-6 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.sites.index') }}" class="text-sm text-gray-500">Annuleren</a>
                <button type="submit" class="btn-primary">Opslaan</button>
            </div>
        </form>
    </div>
</div>
@endsection
