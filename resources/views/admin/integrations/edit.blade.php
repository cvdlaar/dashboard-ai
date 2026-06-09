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

            {{-- Stap 1: Setup instructies --}}
            <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 space-y-1.5">
                <p class="font-semibold text-blue-900 text-sm">Instellen in 3 stappen</p>
                <p><span class="font-medium">1.</span> Ga naar <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline">Google Cloud Console → Credentials</a> en maak een <strong>OAuth 2.0 client ID</strong> aan (type: Web-app).</p>
                <p><span class="font-medium">2.</span> Voeg deze redirect URI toe als toegestane redirect:</p>
                <code class="block bg-blue-100 px-2 py-1 rounded font-mono text-xs break-all">{{ rtrim(config('app.url'), '/') }}/admin/oauth/google/callback</code>
                <p><span class="font-medium">3.</span> Plak de Client ID en Client Secret hieronder en klik daarna op "Autoriseren met Google".</p>
            </div>

            {{-- Verbindingsstatus --}}
            @if($integration->exists && $integration->status === 'connected')
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                <div class="flex items-center text-sm text-green-800">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Gekoppeld{{ $integration->token_expires_at ? ' · token verloopt ' . $integration->token_expires_at->diffForHumans() : '' }}
                </div>
                <a href="{{ route('admin.oauth.google.redirect', [$site, $platform]) }}"
                   class="text-xs text-green-700 underline hover:no-underline">Opnieuw autoriseren</a>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID <span class="text-red-500">*</span></label>
                    <input type="text" name="client_id"
                           placeholder="xxxx.apps.googleusercontent.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret <span class="text-red-500">*</span></label>
                    <input type="password" name="client_secret"
                           placeholder="{{ $integration->exists ? '••••••••' : 'Plak hier je client secret' }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-blue focus:border-transparent">
                </div>
            </div>

            {{-- Autoriseer-knop (na opslaan credentials) --}}
            @if($integration->exists)
            <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between">
                <p class="text-xs text-gray-600">Sla eerst de credentials op, dan kun je autoriseren.</p>
                <a href="{{ route('admin.oauth.google.redirect', [$site, $platform]) }}"
                   class="inline-flex items-center text-sm font-medium px-4 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Autoriseren met Google
                </a>
            </div>
            @endif
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
