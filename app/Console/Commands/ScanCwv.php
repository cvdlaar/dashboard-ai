<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageMetricCwv;
use App\Models\Site;
use App\Models\SiteIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScanCwv extends Command
{
    protected $signature = 'scan:cwv
                            {--site= : Domein van een specifieke site}
                            {--limit=25 : Maximaal aantal URLs te scannen (standaard 25 = gratis limiet)}
                            {--strategy=mobile : mobile of desktop}
                            {--type= : Filter op paginatype: product, category, content}
                            {--force : Scan ook URLs die al recent gescand zijn}';

    protected $description = 'Scan CWV-scores via PageSpeed Insights API';

    private string $apiBase = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function handle(): int
    {
        $sites = Site::where('is_active', true)
            ->when($this->option('site'), fn($q) => $q->where('domain', $this->option('site')))
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Geen actieve sites gevonden.');
            return 1;
        }

        foreach ($sites as $site) {
            $this->processSite($site);
        }

        return 0;
    }

    private function processSite(Site $site): void
    {
        $this->info("Scannen: {$site->name} ({$site->domain})");

        $integration = SiteIntegration::where('site_id', $site->id)
            ->where('platform', 'pagespeed')
            ->first();

        $apiKey   = $integration ? json_decode($integration->credentials ?? '{}', true)['api_key'] ?? null : null;
        $limit    = (int) $this->option('limit');
        $strategy = $this->option('strategy');
        $force    = $this->option('force');

        if ($apiKey) {
            $this->line("  API key aanwezig — max 25.000 requests/dag");
        } else {
            $this->warn("  Geen API key — max 25 requests/dag (gebruik --limit=25)");
        }

        // Haal pagina's op, prioriteer:
        // 1. Nooit gescand
        // 2. Laagste performance score
        // 3. Oudst gescand
        $query = Page::where('site_id', $site->id)
            ->where('is_active', true)
            ->when($this->option('type'), fn($q) => $q->where('type', $this->option('type')));

        if (!$force) {
            // Sla pagina's over die in de laatste 7 dagen gescand zijn
            $recentlyScanned = PageMetricCwv::whereIn('page_id', $query->pluck('id'))
                ->where('strategy', $strategy)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('page_id');

            $query->whereNotIn('id', $recentlyScanned);
        }

        // Prioriteer op prioriteitsscore (hoog eerst) zodat belangrijke pagina's eerder gescand worden
        $pages = $query->orderByRaw('(SELECT MAX(performance_score) FROM page_metrics_cwv WHERE page_id = pages.id) ASC NULLS FIRST')
            ->limit($limit)
            ->get();

        if ($pages->isEmpty()) {
            $this->line("  Geen pagina's om te scannen (alles recent gescand, gebruik --force om opnieuw te scannen).");
            return;
        }

        $this->line("  {$pages->count()} pagina's in wachtrij (van max {$limit})");

        $scanned = 0;
        $failed  = 0;
        $bar     = $this->output->createProgressBar($pages->count());
        $bar->start();

        foreach ($pages as $page) {
            $result = $this->scanUrl($page->url, $strategy, $apiKey);

            if ($result) {
                PageMetricCwv::create(array_merge(['page_id' => $page->id, 'strategy' => $strategy], $result));
                $scanned++;
            } else {
                $failed++;
            }

            $bar->advance();

            // Wacht 1 seconde tussen requests om rate limits te respecteren
            if ($pages->count() > 1) {
                sleep(1);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Klaar: {$scanned} gescand" . ($failed ? ", {$failed} mislukt" : "") . ".");
    }

    private function scanUrl(string $url, string $strategy, ?string $apiKey): ?array
    {
        try {
            $params = [
                'url'      => $url,
                'strategy' => $strategy,
                'category' => ['performance', 'seo', 'accessibility', 'best-practices'],
            ];

            if ($apiKey) {
                $params['key'] = $apiKey;
            }

            $response = Http::timeout(60)
                ->get($this->apiBase, $params);

            if (!$response->successful()) {
                $this->warn("\n  Mislukt voor {$url}: HTTP {$response->status()}");
                return null;
            }

            return $this->parseResponse($response->json());
        } catch (\Exception $e) {
            $this->warn("\n  Fout voor {$url}: " . $e->getMessage());
            return null;
        }
    }

    private function parseResponse(array $data): array
    {
        $categories  = $data['lighthouseResult']['categories'] ?? [];
        $audits      = $data['lighthouseResult']['audits'] ?? [];

        $score = fn(string $key): ?int => isset($categories[$key]['score'])
            ? (int) round($categories[$key]['score'] * 100)
            : null;

        $metric = fn(string $key): ?float => isset($audits[$key]['numericValue'])
            ? round($audits[$key]['numericValue'], 2)
            : null;

        $rating = fn(string $key): ?string => $audits[$key]['displayValue'] ?? null;

        $lcp = $metric('largest-contentful-paint');
        $cls = $metric('cumulative-layout-shift');
        $inp = $metric('interaction-to-next-paint') ?? $metric('experimental-interaction-to-next-paint');

        return [
            'performance_score'    => $score('performance'),
            'seo_score'            => $score('seo'),
            'accessibility_score'  => $score('accessibility'),
            'best_practices_score' => $score('best-practices'),
            'lcp'                  => $lcp,
            'cls'                  => $cls,
            'inp'                  => $inp,
            'fcp'                  => $metric('first-contentful-paint'),
            'ttfb'                 => $metric('server-response-time'),
            'tbt'                  => $metric('total-blocking-time'),
            'speed_index'          => $metric('speed-index'),
            'lcp_rating'           => $lcp ? $this->rateLcp($lcp) : null,
            'cls_rating'           => $cls !== null ? $this->rateCls($cls) : null,
            'inp_rating'           => $inp ? $this->rateInp($inp) : null,
        ];
    }

    private function rateLcp(float $ms): string
    {
        return match(true) {
            $ms <= 2500  => 'good',
            $ms <= 4000  => 'needs-improvement',
            default      => 'poor',
        };
    }

    private function rateCls(float $cls): string
    {
        return match(true) {
            $cls <= 0.1  => 'good',
            $cls <= 0.25 => 'needs-improvement',
            default      => 'poor',
        };
    }

    private function rateInp(float $ms): string
    {
        return match(true) {
            $ms <= 200   => 'good',
            $ms <= 500   => 'needs-improvement',
            default      => 'poor',
        };
    }
}
