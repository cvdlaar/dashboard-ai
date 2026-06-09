<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageMetricGsc;
use App\Models\Site;
use App\Models\SiteIntegration;
use App\Services\GoogleOAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncGsc extends Command
{
    protected $signature = 'sync:gsc
                            {--site= : Domein van een specifieke site}
                            {--days=28 : Aantal dagen terug om op te halen}
                            {--limit=5000 : Max rijen per API-request}';

    protected $description = 'Synchroniseer Google Search Console data per pagina';

    private const API_BASE = 'https://searchconsole.googleapis.com/webmasters/v3/sites';

    public function handle(GoogleOAuth $oauth): int
    {
        $sites = Site::where('is_active', true)
            ->when($this->option('site'), fn($q) => $q->where('domain', $this->option('site')))
            ->get();

        if ($sites->isEmpty()) {
            $this->error('Geen actieve sites gevonden.');
            return 1;
        }

        foreach ($sites as $site) {
            $this->processSite($site, $oauth);
        }

        return 0;
    }

    private function processSite(Site $site, GoogleOAuth $oauth): void
    {
        $this->info("GSC sync: {$site->name} ({$site->domain})");

        $integration = SiteIntegration::where('site_id', $site->id)
            ->where('platform', 'google_search_console')
            ->first();

        if (!$integration || $integration->status !== 'connected') {
            $this->warn("  Niet gekoppeld — sla over. Koppel eerst via Beheer → Koppelingen.");
            return;
        }

        $creds        = json_decode($integration->credentials ?? '{}', true);
        $refreshToken = $creds['refresh_token'] ?? null;
        $clientId     = $creds['client_id'] ?? null;
        $clientSecret = $creds['client_secret'] ?? null;
        $siteUrl      = $integration->settings['site_url'] ?? "https://{$site->domain}/";

        if (!$refreshToken || !$clientId || !$clientSecret) {
            $this->error("  Ontbrekende credentials (client_id, client_secret of refresh_token).");
            return;
        }

        try {
            $accessToken = $oauth->refreshAccessToken($refreshToken, $clientId, $clientSecret);
        } catch (\Exception $e) {
            $this->error("  Token refresh mislukt: " . $e->getMessage());
            $integration->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            return;
        }

        $days      = (int) $this->option('days');
        $endDate   = now()->subDay()->format('Y-m-d');
        $startDate = now()->subDays($days)->format('Y-m-d');

        $this->line("  Site URL: {$siteUrl}");
        $this->line("  Periode: {$startDate} t/m {$endDate}");

        $rows = $this->fetchGscData($accessToken, $siteUrl, $startDate, $endDate);

        if (empty($rows)) {
            $this->warn("  Geen data ontvangen van GSC.");
            return;
        }

        $this->line("  " . count($rows) . " rijen ontvangen");
        $this->saveRows($rows, $site, $startDate, $endDate);

        $integration->update([
            'last_sync_at'  => now(),
            'error_message' => null,
        ]);
    }

    private function fetchGscData(string $token, string $siteUrl, string $startDate, string $endDate): array
    {
        // GSC verwacht de site URL URL-encoded in het pad
        $encodedSite = urlencode($siteUrl);
        $endpoint    = self::API_BASE . "/{$encodedSite}/searchAnalytics/query";
        $limit       = (int) $this->option('limit');

        $allRows   = [];
        $startRow  = 0;

        do {
            $response = Http::withToken($token)
                ->timeout(60)
                ->post($endpoint, [
                    'startDate'  => $startDate,
                    'endDate'    => $endDate,
                    'dimensions' => ['page', 'query'],
                    'rowLimit'   => min($limit, 25000),
                    'startRow'   => $startRow,
                ]);

            if ($response->status() === 403) {
                $this->error("  Geen toegang. Controleer of de service account / OAuth gebruiker toegang heeft tot {$siteUrl} in Search Console.");
                return [];
            }

            if (!$response->successful()) {
                $this->error("  API fout {$response->status()}: " . $response->body());
                return [];
            }

            $rows     = $response->json('rows', []);
            $allRows  = array_merge($allRows, $rows);
            $startRow += count($rows);

            // Stoppen als we minder dan het maximum ontvangen (geen volgende pagina)
        } while (count($rows) === min($limit, 25000) && count($allRows) < 100000);

        return $allRows;
    }

    private function saveRows(array $rows, Site $site, string $startDate, string $endDate): void
    {
        $saved   = 0;
        $skipped = 0;

        // Laad alle pagina-URLs van deze site in geheugen voor snelle lookup
        $pagesByUrl = Page::where('site_id', $site->id)
            ->get(['id', 'url'])
            ->keyBy(fn($p) => rtrim($p->url, '/'));

        // Verwijder bestaande GSC data voor deze periode zodat we met schone staat beginnen
        $pageIds = $pagesByUrl->pluck('id');
        PageMetricGsc::whereIn('page_id', $pageIds)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->delete();

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $batch = [];

        foreach ($rows as $row) {
            $keys      = $row['keys'] ?? [];
            $pageUrl   = rtrim($keys[0] ?? '', '/');
            $query     = $keys[1] ?? null;

            $page = $pagesByUrl->get($pageUrl);

            if (!$page) {
                // Pagina nog niet in database → aanmaken
                $path = parse_url($pageUrl, PHP_URL_PATH) ?? '/';
                $page = Page::create([
                    'site_id'   => $site->id,
                    'url'       => $pageUrl,
                    'path'      => $path,
                    'type'      => 'content',
                    'is_active' => true,
                ]);
                $pagesByUrl->put($pageUrl, $page);
            }

            $batch[] = [
                'page_id'     => $page->id,
                'date'        => $endDate, // Gebruik einde periode als representatieve datum
                'query'       => $query,
                'clicks'      => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr'         => round((float) ($row['ctr'] ?? 0), 6),
                'position'    => round((float) ($row['position'] ?? 0), 2),
                'device'      => null,
                'country'     => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            // Batch insert per 500 rijen
            if (count($batch) >= 500) {
                PageMetricGsc::insert($batch);
                $saved += count($batch);
                $batch  = [];
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            PageMetricGsc::insert($batch);
            $saved += count($batch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("  {$saved} GSC rijen opgeslagen" . ($skipped ? ", {$skipped} overgeslagen" : '') . ".");
    }
}
