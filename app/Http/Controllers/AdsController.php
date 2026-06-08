<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();
        $platform = $request->get('platform', 'google');

        $metrics = \App\Models\PageMetricAds::with('page.site')
            ->where('platform', $platform)
            ->when($request->site, fn($q) => $q->whereHas('page', fn($q2) => $q2->where('site_id', $request->site)))
            ->whereDate('date', '>=', now()->subDays(28))
            ->selectRaw('page_id, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(cost) as cost, SUM(conversions) as conversions, SUM(conversion_value) as conversion_value')
            ->groupBy('page_id')
            ->orderByDesc('cost')
            ->with('page')
            ->paginate(50)
            ->withQueryString();

        $totals = \App\Models\PageMetricAds::where('platform', $platform)
            ->whereDate('date', '>=', now()->subDays(28))
            ->selectRaw('SUM(cost) as total_cost, SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(conversion_value) as total_value')
            ->first();

        return view('ads.index', compact('sites', 'metrics', 'platform', 'totals'));
    }
}
