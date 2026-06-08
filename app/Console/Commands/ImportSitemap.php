<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportSitemap extends Command
{
    protected $signature = 'import:sitemap
                            {--site= : Domein van een specifieke site (bijv. logistiekconcurrent.nl)}
                            {--dry-run : Toon wat geïmporteerd zou worden zonder op te slaan}';

    protected $description = 'Importeer URLs uit de sitemap(s) van alle actieve sites';

    // URL-patronen om het type te bepalen (volgorde is bepalend: eerst matchen wint)
    private array $patterns = [
        'product' => [
            '/product/',        // WooCommerce: /product/naam/
            '/products/',
            '/p/',
            '/item/',
            '/artikel/',
            '/bestellen/',
        ],
        'category' => [
            '/product-category/',   // WooCommerce categoriepagina's
            '/product-categorie/',
            '/categorie/',
            '/category/',
            '/categories/',
            '/cat/',
            '/afdeling/',
            '/assortiment/',
            '/merk/',
            '/brand/',
            '/c/',
        ],
        'content' => [
            '/blog/',
            '/nieuws/',
            '/news/',
            '/over-ons',
            '/over-',
            '/contact',
            '/faq',
            '/veelgestelde-vragen',
            '/service',
            '/helpdesk',
            '/ondersteuning',
            '/support',
            '/pagina/',
            '/page/',
            '/algemene-',
            '/privacy',
            '/cookie',
            '/sitemap',
        ],
    ];

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
        $sitemapUrl = $site->sitemap_url;

        if (!$sitemapUrl) {
            $this->warn("  [{$site->domain}] Geen sitemap URL ingesteld — sla over.");
            return;
        }

        $this->info("Verwerken: {$site->name} ({$site->domain})");
        $this->line("  Sitemap: {$sitemapUrl}");

        $urls = $this->fetchUrls($sitemapUrl, $site);

        if (empty($urls)) {
            $this->warn("  Geen URLs gevonden in sitemap.");
            return;
        }

        $this->line("  {$this->count($urls)} URLs gevonden");

        if ($this->option('dry-run')) {
            $this->showPreview($urls);
            return;
        }

        $this->saveUrls($urls, $site);
    }

    private function fetchUrls(string $sitemapUrl, Site $site): array
    {
        $xml = $this->fetchXml($sitemapUrl);

        if (!$xml) {
            $this->error("  Kon sitemap niet ophalen: {$sitemapUrl}");
            return [];
        }

        // Sitemap index: bevat links naar sub-sitemaps
        if (isset($xml->sitemap)) {
            return $this->processSitemapIndex($xml, $site);
        }

        // Gewone sitemap met <url> entries
        if (isset($xml->url)) {
            return $this->parseUrlEntries($xml, $site);
        }

        return [];
    }

    private function processSitemapIndex(\SimpleXMLElement $xml, Site $site): array
    {
        $allUrls = [];
        $sitemapCount = count($xml->sitemap);
        $this->line("  Sitemap index gevonden met {$sitemapCount} sub-sitemaps");

        $bar = $this->output->createProgressBar($sitemapCount);
        $bar->start();

        foreach ($xml->sitemap as $sitemapEntry) {
            $subUrl = (string) $sitemapEntry->loc;
            if ($subUrl) {
                $subXml = $this->fetchXml($subUrl);
                if ($subXml && isset($subXml->url)) {
                    $urls = $this->parseUrlEntries($subXml, $site);
                    $allUrls = array_merge($allUrls, $urls);
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $allUrls;
    }

    private function parseUrlEntries(\SimpleXMLElement $xml, Site $site): array
    {
        $urls = [];

        foreach ($xml->url as $entry) {
            $url = (string) $entry->loc;

            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $path     = parse_url($url, PHP_URL_PATH) ?? '/';
            $language = $this->detectLanguage($url, $path, $site);

            // Betrouwbaarste signaal: aanwezigheid van <image:image> → product
            // Gebruik prefix=true zodat het namespace-URI niet uitmaakt (Magento varieert hierin)
            $imageNs  = $entry->children('image', true);
            $hasImage = isset($imageNs->image);

            // Fallback: <PageMap> met thumbnail is ook een productsignaal (Magento)
            if (!$hasImage) {
                $entryXml = $entry->asXML();
                $hasImage = str_contains($entryXml, '<image:image>') || str_contains($entryXml, 'DataObject type="thumbnail"');
            }

            $title = null;
            if ($hasImage && isset($imageNs->image->title)) {
                $title = (string) $imageNs->image->title;
            }

            $type = $hasImage ? 'product' : $this->detectType($path);

            $urls[] = [
                'url'      => rtrim($url, '/'),
                'path'     => $path,
                'type'     => $type,
                'language' => $language,
                'title'    => $title,
            ];
        }

        return $urls;
    }

    private function detectLanguage(string $url, string $path, Site $site): string
    {
        $languages = $site->languages ?? ['nl'];

        if (count($languages) <= 1) {
            return $languages[0] ?? 'nl';
        }

        // Zoek taal-prefix in het pad: /fr/, /nl/, etc.
        foreach ($languages as $lang) {
            if (str_starts_with($path, "/{$lang}/") || $path === "/{$lang}") {
                return $lang;
            }
        }

        // Fallback: eerste taal
        return $languages[0];
    }

    private function detectType(string $path): string
    {
        $path = strtolower($path);

        foreach ($this->patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($path, $keyword)) {
                    return $type;
                }
            }
        }

        // Heuristiek als fallback (alleen bereikt als er geen <image:image> aanwezig is):
        // depth-0 = homepage, depth-1 zonder content-match = categorie,
        // dieper zonder content-match = categorie (sub-categorie of landingspagina)
        $segments = array_filter(explode('/', $path));
        $depth    = count($segments);

        if ($depth === 0) {
            return 'content';
        }

        return 'category';
    }

    private function saveUrls(array $urls, Site $site): void
    {
        $new = 0;
        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($urls));
        $bar->start();

        foreach ($urls as $data) {
            $exists = Page::where('site_id', $site->id)
                ->where('url', $data['url'])
                ->exists();

            if ($exists) {
                $updateData = [
                    'path'     => $data['path'],
                    'type'     => $data['type'],
                    'language' => $data['language'],
                ];
                if (!empty($data['title'])) {
                    $updateData['title'] = $data['title'];
                }
                Page::where('site_id', $site->id)
                    ->where('url', $data['url'])
                    ->update($updateData);
                $updated++;
            } else {
                Page::create([
                    'site_id'   => $site->id,
                    'url'       => $data['url'],
                    'path'      => $data['path'],
                    'type'      => $data['type'],
                    'language'  => $data['language'],
                    'title'     => $data['title'] ?? null,
                    'is_active' => true,
                ]);
                $new++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("  Klaar: {$new} nieuw, {$updated} bijgewerkt.");
    }

    private function fetchXml(string $url): ?\SimpleXMLElement
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'LC-Dashboard-Bot/1.0'])
                ->get($url);

            if (!$response->successful()) {
                $this->warn("  HTTP {$response->status()} voor {$url}");
                return null;
            }

            $content = $response->body();

            // Verwijder eventuele BOM of whitespace voor de XML declaratie
            $content = ltrim($content);
            if (str_starts_with($content, "\xEF\xBB\xBF")) {
                $content = substr($content, 3);
            }

            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if ($xml === false) {
                $errors = libxml_get_errors();
                $this->warn("  XML parse fout: " . ($errors[0]->message ?? 'onbekend'));
                libxml_clear_errors();
                return null;
            }

            return $xml;
        } catch (\Exception $e) {
            $this->warn("  Fout bij ophalen {$url}: " . $e->getMessage());
            return null;
        }
    }

    private function showPreview(array $urls): void
    {
        $byType = collect($urls)->groupBy('type');
        $this->table(
            ['Type', 'Aantal', 'Voorbeelden'],
            $byType->map(fn($items, $type) => [
                $type,
                count($items),
                collect($items)->take(2)->pluck('path')->implode(', '),
            ])->values()->toArray()
        );
    }

    private function count(array $urls): int
    {
        return count($urls);
    }
}
