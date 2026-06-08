<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SeoController extends Controller
{
    public function index(Request $request)
    {
        $sites = \App\Models\Site::where('is_active', true)->get();

        $pages = \App\Models\Page::with(['site', 'latestGscMetric'])
            ->when($request->site, fn($q) => $q->where('site_id', $request->site))
            ->whereHas('gscMetrics')
            ->get()
            ->sortBy(fn($p) => $p->latestGscMetric?->position ?? 999);

        $topQueries = \App\Models\PageMetricGsc::whereNotNull('query')
            ->whereDate('date', '>=', now()->subDays(28))
            ->selectRaw('query, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks, AVG(position) as avg_position')
            ->groupBy('query')
            ->orderByDesc('total_impressions')
            ->limit(20)
            ->get();

        return view('seo.index', compact('sites', 'pages', 'topQueries'));
    }
}
