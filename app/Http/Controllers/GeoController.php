<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageMetricGeo;
use App\Models\Site;
use App\Models\SiteGeoCheck;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::where('is_active', true)->get();

        $currentSiteId = $request->query('site_id')
            ?? session('current_site_id')
            ?? $sites->first()?->id;
        $currentSite = $sites->firstWhere('id', $currentSiteId) ?? $sites->first();

        if ($request->query('site_id')) {
            session(['current_site_id' => $currentSite->id]);
        }

        $query = Page::where('site_id', $currentSite->id)
            ->where('is_active', true)
            ->with(['latestGeoMetric'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->issue, fn($q) => $q->whereHas('latestGeoMetric', fn($q2) =>
                $q2->whereJsonContains('issues', $request->issue)
            ));

        $sort = $request->sort ?? 'score_asc';
        match ($sort) {
            'score_asc'  => $query->orderByRaw('(SELECT geo_score FROM page_metrics_geo WHERE page_id = pages.id ORDER BY scored_at DESC LIMIT 1) ASC NULLS FIRST'),
            'score_desc' => $query->orderByRaw('(SELECT geo_score FROM page_metrics_geo WHERE page_id = pages.id ORDER BY scored_at DESC LIMIT 1) DESC NULLS LAST'),
            default      => $query->orderBy('url'),
        };

        $pages = $query->paginate(50)->withQueryString();

        $siteGeoCheck = SiteGeoCheck::where('site_id', $currentSite->id)
            ->latest('checked_at')
            ->first();

        $geoMetrics = PageMetricGeo::whereIn('page_id',
            Page::where('site_id', $currentSite->id)->pluck('id')
        );

        $stats = [
            'total_scored'      => (clone $geoMetrics)->count(),
            'avg_score'         => (int) round((clone $geoMetrics)->avg('geo_score') ?? 0),
            'score_good'        => (clone $geoMetrics)->where('geo_score', '>=', 75)->count(),
            'score_warning'     => (clone $geoMetrics)->whereBetween('geo_score', [45, 74])->count(),
            'score_poor'        => (clone $geoMetrics)->where('geo_score', '<', 45)->count(),
            'has_json_ld'       => (clone $geoMetrics)->where('has_json_ld', true)->count(),
            'has_faq'           => (clone $geoMetrics)->where('has_faq_block', true)->count(),
            'has_comparison'    => (clone $geoMetrics)->where('has_comparison_content', true)->count(),
            'has_citable_intro' => (clone $geoMetrics)->where('has_citable_intro', true)->count(),
            'has_organization'  => (clone $geoMetrics)->where('has_organization_schema', true)->count(),
            'has_author'        => (clone $geoMetrics)->where('has_author_schema', true)->count(),
            'has_list_content'  => (clone $geoMetrics)->where('has_list_content', true)->count(),
            'has_toc'           => (clone $geoMetrics)->where('has_table_of_contents', true)->count(),
            'has_freshness'     => (clone $geoMetrics)->whereNotNull('date_modified')->count(),
            'has_ext_links'     => (clone $geoMetrics)->where('external_link_count', '>=', 1)->count(),
        ];

        $knownBots = SiteGeoCheck::$knownBots;

        return view('geo.index', compact(
            'sites', 'currentSite', 'pages', 'stats', 'siteGeoCheck', 'knownBots', 'sort'
        ));
    }
}
