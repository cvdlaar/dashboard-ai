@extends('layouts.app')

@section('title', 'Productgroep bewerken — ' . $productGroup->name)

@section('content')
<div class="max-w-4xl space-y-6">

    <div>
        <a href="{{ route('admin.product-groups.index') }}" class="text-sm text-brand-blue hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Terug naar productgroepen
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Naam + specialisten --}}
    <div class="card">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Naam & specialisten</h2>

        <form method="POST" action="{{ route('admin.product-groups.update', $productGroup) }}">
            @csrf @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Naam <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $productGroup->name) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Omschrijving</label>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">{{ old('description', $productGroup->description) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Specialisten</label>
                @forelse($specialists as $user)
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                               {{ $productGroup->users->contains($user->id) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-2.5">
                        <span class="text-sm text-gray-700">{{ $user->name }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $user->email }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-400 italic">Nog geen specialisten aangemaakt.</p>
                @endforelse
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="btn-primary">Opslaan</button>
            </div>
        </form>
    </div>

    {{-- URL's koppelen --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">URL's koppelen</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $productGroup->pages->count() }} pagina's gekoppeld
                    @if($unassignedPages->count() > 0)
                        · {{ $unassignedPages->count() }} nog ongekoppeld
                    @endif
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.product-groups.assign-pages', $productGroup) }}">
            @csrf

            {{-- Zoekbalk --}}
            <div class="mb-3">
                <input type="text" id="page-search" placeholder="Zoek op URL of pad..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
                {{-- Al gekoppelde pagina's --}}
                @if($productGroup->pages->count() > 0)
                <div class="bg-green-50 px-4 py-2 border-b border-gray-200">
                    <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Gekoppeld aan deze groep</p>
                </div>
                <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto" id="assigned-list">
                    @foreach($productGroup->pages->sortBy('url') as $page)
                    <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer page-row" data-url="{{ strtolower($page->url) }}">
                        <input type="checkbox" name="page_ids[]" value="{{ $page->id }}" checked
                               class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-3 flex-shrink-0">
                        <span class="text-xs text-gray-700 truncate">{{ $page->path ?: $page->url }}</span>
                        <span class="ml-auto text-xs text-gray-400 flex-shrink-0">{{ $page->type }}</span>
                    </label>
                    @endforeach
                </div>
                @endif

                {{-- Ongekoppelde pagina's --}}
                @if($unassignedPages->count() > 0)
                @if($productGroup->pages->count() > 0)
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 border-t">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Overige pagina's (ongekoppeld)</p>
                </div>
                @endif
                <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto" id="unassigned-list">
                    @foreach($unassignedPages as $page)
                    <label class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer page-row" data-url="{{ strtolower($page->url) }}">
                        <input type="checkbox" name="page_ids[]" value="{{ $page->id }}"
                               class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-3 flex-shrink-0">
                        <span class="text-xs text-gray-700 truncate">{{ $page->path ?: $page->url }}</span>
                        <span class="ml-auto text-xs text-gray-400 flex-shrink-0">{{ $page->type }}</span>
                    </label>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="flex items-center justify-between mt-4">
                <p class="text-xs text-gray-400">Vink aan welke pagina's bij deze groep horen. Ongevinkte gekoppelde pagina's worden losgekoppeld.</p>
                <button type="submit" class="btn-primary text-sm">Opslaan</button>
            </div>
        </form>
    </div>

</div>

<script>
document.getElementById('page-search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.page-row').forEach(row => {
        row.style.display = row.dataset.url.includes(q) ? '' : 'none';
    });
});
</script>
@endsection
