<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageMetricGeo;
use App\Models\Site;
use App\Models\SiteGeoCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    public function updateMentionShare(Request $request)
    {
        $validated = $request->validate([
            'site_id'       => 'required|exists:sites,id',
            'reddit_url'    => 'nullable|url|max:500',
            'wikipedia_url' => 'nullable|url|max:500',
            'youtube_url'   => 'nullable|url|max:500',
            'wikidata_id'   => 'nullable|string|max:50',
        ]);

        $check = SiteGeoCheck::firstOrNew(['site_id' => $validated['site_id']]);
        $check->reddit_url    = $validated['reddit_url']    ?? null;
        $check->wikipedia_url = $validated['wikipedia_url'] ?? null;
        $check->youtube_url   = $validated['youtube_url']   ?? null;
        $check->wikidata_id   = $validated['wikidata_id']   ?? null;
        if (!$check->checked_at) {
            $check->checked_at = now();
        }
        $check->save();

        return redirect()->route('geo.index', ['site_id' => $validated['site_id']])
            ->with('success', 'Mention share opgeslagen.');
    }

    public function export(Request $request)
    {
        $sites = Site::where('is_active', true)->get();
        $siteId = $request->site_id ?? session('current_site_id') ?? $sites->first()?->id;
        $site   = $sites->firstWhere('id', $siteId) ?? $sites->first();

        $pages = Page::where('site_id', $site->id)
            ->where('is_active', true)
            ->with(['latestGeoMetric'])
            ->orderBy('url')
            ->get();

        $filename = 'geo-export-' . str_replace('.', '-', $site->domain) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'URL', 'Type', 'GEO score',
            'JSON-LD', 'FAQ schema', 'Product schema', 'Breadcrumb', 'Organisatie schema', 'Auteur schema',
            'Open Graph', 'Canonical', 'Meta description',
            'H1', 'Inhoudsopgave', 'Lijsten/tabellen',
            'Citeerbare intro', 'Intro woorden', 'Vraagkoppen', 'FAQ-blok', 'Vergelijkende copy',
            'Externe links', 'dateModified', 'Versheid (dagen)',
            'Aandachtspunten',
        ];

        $callback = function () use ($pages, $columns) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($handle, $columns, ';');

            foreach ($pages as $page) {
                $geo = $page->latestGeoMetric;
                fputcsv($handle, [
                    $page->url,
                    $page->type,
                    $geo?->geo_score ?? '',
                    $geo ? ($geo->has_json_ld ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_faq_schema ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_product_schema ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_breadcrumb_schema ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_organization_schema ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_author_schema ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_open_graph ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_canonical ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_meta_description ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_h1 ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_table_of_contents ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_list_content ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_citable_intro ? 'Ja' : 'Nee') : '',
                    $geo?->intro_word_count ?? '',
                    $geo?->question_heading_count ?? '',
                    $geo ? ($geo->has_faq_block ? 'Ja' : 'Nee') : '',
                    $geo ? ($geo->has_comparison_content ? 'Ja' : 'Nee') : '',
                    $geo?->external_link_count ?? '',
                    $geo?->date_modified?->format('Y-m-d') ?? '',
                    $geo?->freshness_days ?? '',
                    $geo ? implode(' | ', $geo->issues ?? []) : '',
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function llmsTxt(Request $request)
    {
        $sites = Site::where('is_active', true)->get();
        $siteId = $request->site_id ?? session('current_site_id') ?? $sites->first()?->id;
        $site   = $sites->firstWhere('id', $siteId) ?? $sites->first();

        // Haal pagina's op gegroepeerd per type, gesorteerd op GSC-impressies
        $pages = Page::where('site_id', $site->id)
            ->where('is_active', true)
            ->with(['latestGscMetric', 'latestGeoMetric'])
            ->get()
            ->sortByDesc(fn($p) => $p->latestGscMetric?->impressions ?? 0);

        $byType = $pages->groupBy('type');

        // Genereer de llms.txt inhoud
        $content = $this->generateLlmsTxt($site, $byType);

        // Download-modus
        if ($request->boolean('download')) {
            return response($content, 200, [
                'Content-Type'        => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="llms.txt"',
            ]);
        }

        return view('geo.llms-txt', compact('sites', 'site', 'siteId', 'content'));
    }

    private function generateLlmsTxt(Site $site, $byType): string
    {
        $lines = [];

        $lines[] = "# {$site->name}";
        $lines[] = "";
        $lines[] = "> {$site->name} is een online platform voor logistieke producten en verpakkingsmateriaal.";
        $lines[] = "> Website: https://{$site->domain}";
        $lines[] = "";

        $typeLabels = [
            'category' => 'Categorieën',
            'content'  => 'Content',
            'product'  => 'Producten',
        ];

        // Categorieën eerst (meest waardevol voor LLMs als navigatiestructuur)
        $typeOrder = ['category', 'content', 'product'];

        foreach ($typeOrder as $type) {
            $typePages = $byType->get($type);
            if (!$typePages || $typePages->isEmpty()) continue;

            $label = $typeLabels[$type] ?? ucfirst($type);
            $lines[] = "## {$label}";
            $lines[] = "";

            // Producten: max 200 (te veel voor llms.txt), rest types volledig
            $limit = $type === 'product' ? 200 : 500;
            $shown = $typePages->take($limit);

            foreach ($shown as $page) {
                $title = $page->title ?: basename(rtrim($page->path, '/'));
                if (!$title) $title = $page->path;

                $line = "- [{$title}](https://{$site->domain}{$page->path})";

                // Voeg meta description toe als beschrijving als die beschikbaar is
                if (!empty($page->meta_description)) {
                    $line .= ": " . $page->meta_description;
                }

                $lines[] = $line;
            }

            if ($typePages->count() > $limit) {
                $lines[] = "";
                $lines[] = "> *En " . ($typePages->count() - $limit) . " meer {$label}...*";
            }

            $lines[] = "";
        }

        return implode("\n", $lines);
    }
}
