@extends('layouts.app')

@section('title', $platformConfig['label'] . ' instellen — ' . $site->name)

@section('content')
<div class="max-w-2xl">

    <div class="mb-6">
        <a href="{{ route('admin.integrations.index') }}" class="text-sm text-brand-blue hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Terug naar koppelingen
        </a>
    </div>

    <div class="card">
        <div class="flex items-center mb-6 pb-6 border-b border-gray-100">
            <div class="w-12 h-12 rounded-xl bg-brand-blue flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $platformConfig['label'] }}</h2>
                <p class="text-sm text-gray-500">{{ $site->name }} — {{ $site->domain }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.integrations.update', [$site, $platform]) }}">
            @csrf
            @method('PUT')

            {{-- Google OAuth platforms --}}
            @if($platformConfig['auth'] === 'oauth')
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                <p class="text-sm font-medium text-blue-900 mb-1">OAuth 2.0 koppeling</p>
                <p class="text-xs text-blue-700">
                    Maak een OAuth client aan in
                    <a href="https://console.cloud.google.com" target="_blank" class="underline">Google Cloud Console</a>
                    en voer de credentials hier in. De redirect URI moet zijn:<br>
                    <code class="bg-blue-100 px-1 rounded text-xs font-mono">{{ config('app.url') }}/admin/integrations/google/callback</code>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                    <input type="text" name="client_id"
                           placeholder="xxxx.apps.googleusercontent.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                    <p class="text-xs text-gray-400 mt-1">Niet verplicht als Google al globaal is ingesteld</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                    <input type="password" name="client_secret"
                           placeholder="••••••••"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token</label>
                <input type="password" name="refresh_token"
                       value="{{ $integration->exists ? '••••••••' : '' }}"
                       placeholder="Wordt automatisch ingesteld na OAuth-flow"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">Genereer via de OAuth-flow of voer handmatig in vanuit Google Cloud</p>
            </div>
            @endif

            {{-- Platform-specifieke instellingen --}}
            @if(in_array('site_url', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Site URL (Search Console)</label>
                <input type="url" name="site_url"
                       value="{{ $integration->settings['site_url'] ?? 'https://' . $site->domain . '/' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">Exacte URL zoals in Search Console staat (inclusief trailing slash)</p>
            </div>
            @endif

            @if(in_array('property_id', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">GA4 Property ID</label>
                <input type="text" name="property_id"
                       value="{{ $integration->settings['property_id'] ?? '' }}"
                       placeholder="properties/123456789"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>
            @endif

            @if(in_array('customer_id', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ $platform === 'bing_ads' ? 'Customer ID' : 'Google Ads Customer ID' }}
                </label>
                <input type="text" name="customer_id"
                       value="{{ $integration->settings['customer_id'] ?? '' }}"
                       placeholder="{{ $platform === 'google_ads' ? '123-456-7890' : '12345678' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>
            @endif

            @if(in_array('manager_id', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Manager Account ID <span class="text-gray-400 font-normal">(optioneel)</span></label>
                <input type="text" name="manager_id"
                       value="{{ $integration->settings['manager_id'] ?? '' }}"
                       placeholder="MCC account ID"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>
            @endif

            @if(in_array('account_id', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Microsoft Ads Account ID</label>
                <input type="text" name="account_id"
                       value="{{ $integration->settings['account_id'] ?? '' }}"
                       placeholder="12345678"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
            </div>
            @endif

            @if(in_array('feed_url', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Channable Feed URL</label>
                <input type="url" name="feed_url"
                       value="{{ $integration->getCredential('feed_url') ?? '' }}"
                       placeholder="https://feeds.channable.com/..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">De XML of CSV feed URL uit Channable (inclusief eventuele token in de URL)</p>
            </div>
            @endif

            @if(in_array('api_key', $platformConfig['fields']))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    PageSpeed API Key <span class="text-gray-400 font-normal">(optioneel)</span>
                </label>
                <input type="text" name="api_key"
                       value="{{ $integration->getCredential('api_key') ?? '' }}"
                       placeholder="AIzaSy..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">
                    Zonder API key: 25 requests/dag. Met key: 25.000/dag.
                    Aanmaken via <a href="https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com" target="_blank" class="text-brand-blue underline">Google Cloud Console</a>.
                </p>
            </div>
            @endif

            {{-- Sync schema --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Synchronisatie schema</label>
                <select name="sync_schedule"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                    <option value="manual" {{ ($integration->sync_schedule ?? 'manual') === 'manual' ? 'selected' : '' }}>Handmatig</option>
                    <option value="daily"  {{ ($integration->sync_schedule ?? '') === 'daily'  ? 'selected' : '' }}>Dagelijks</option>
                    <option value="weekly" {{ ($integration->sync_schedule ?? '') === 'weekly' ? 'selected' : '' }}>Wekelijks</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Bij dagelijks of wekelijks wordt automatisch gesynchroniseerd via de scheduler</p>
            </div>

            <div class="flex items-center justify-between pt-6 border-t border-gray-100 mt-6">
                <a href="{{ route('admin.integrations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    Annuleren
                </a>
                <button type="submit" class="btn-primary">
                    Opslaan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
