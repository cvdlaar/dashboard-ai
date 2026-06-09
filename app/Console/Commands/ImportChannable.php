<?php

namespace App\Console\Commands;

use App\Models\ChannableData;
use App\Models\Page;
use App\Models\Site;
use App\Models\SiteIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportChannable extends Command
{
    protected $signature = 'import:channable
                            {--site= : Domein van een specifieke site}
                            {--dry-run : Toon wat geïmporteerd zou worden zonder op te slaan}';

    protected $description = 'Importeer producten uit de Channable feed en koppel aan pages';

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
        $integration = SiteIntegration::where('site_id', $site->id)
            ->where('platform', 'channable')
            ->first();

        if (!$integration) {
            $this->warn("[{$site->domain}] Geen Channable koppeling ingesteld — sla over.");
            return;
        }

        $creds   = json_decode($integration->credentials ?? '{}', true);
        $feedUrl = $creds['feed_url'] ?? null;

        if (!$feedUrl) {
            $this->warn("[{$site->domain}] Geen feed URL in Channable koppeling.");
            return;
        }

        $this->info("Verwerken: {$site->name} ({$site->domain})");
        $this->line("  Feed: {$feedUrl}");

        $products = $this->fetchFeed($feedUrl);

        if (empty($products)) {
            $this->warn("  Geen producten gevonden in feed.");
            return;
        }

        $this->line("  " . count($products) . " producten gevonden");

        if ($this->option('dry-run')) {
            $this->showPreview($products);
            return;
        }

        $this->saveProducts($products, $site);
    }

    private function fetchFeed(string $url): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders(['User-Agent' => 'LC-Dashboard-Bot/1.0'])
                ->get($url);

            if (!$response->successful()) {
                $this->error("  HTTP {$response->status()} voor feed URL");
                return [];
            }

            $content = ltrim($response->body());
            if (str_starts_with($content, "\xEF\xBB\xBF")) {
                $content = substr($content, 3);
            }

            // Detecteer formaat
            if (str_contains($content, '<rss') || str_contains($content, '<feed') || str_contains($content, '<item>')) {
                return $this->parseXmlFeed($content);
            }

            // CSV fallback
            return $this->parseCsvFeed($content);
        } catch (\Exception $e) {
            $this->error("  Fout bij ophalen feed: " . $e->getMessage());
            return [];
        }
    }

    private function parseXmlFeed(string $content): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $this->error("  XML parse fout");
            return [];
        }

        // Google Shopping / RSS formaat
        $gNs    = 'http://base.google.com/ns/1.0';
        $items  = $xml->channel->item ?? $xml->item ?? [];
        $products = [];

        foreach ($items as $item) {
            $g = $item->children($gNs);

            $link  = (string) ($item->link ?? $g->link ?? '');
            $title = (string) ($item->title ?? $g->title ?? '');

            if (!$link || !filter_var($link, FILTER_VALIDATE_URL)) {
                continue;
            }

            $priceRaw     = (string) ($g->price ?? $item->price ?? '');
            $salePriceRaw = (string) ($g->sale_price ?? $item->sale_price ?? '');

            $price     = $this->parsePrice($priceRaw);
            $salePrice = $this->parsePrice($salePriceRaw);

            // Marge: zoek in custom labels en expliciete velden
            $margin = $this->detectMargin($g, $item, $price, $salePrice);

            $products[] = [
                'url'          => rtrim($link, '/'),
                'sku'          => (string) ($g->id ?? $g->mpn ?? $item->id ?? ''),
                'title'        => $title,
                'price'        => $price,
                'sale_price'   => $salePrice,
                'margin'       => $margin['ratio'],
                'margin_amount'=> $margin['amount'],
                'brand'        => (string) ($g->brand ?? $item->brand ?? ''),
                'category'     => (string) ($g->product_type ?? $g->google_product_category ?? $item->product_type ?? ''),
                'availability' => (string) ($g->availability ?? $item->availability ?? ''),
            ];
        }

        return $products;
    }

    private function parseCsvFeed(string $content): array
    {
        $lines    = explode("\n", trim($content));
        $headers  = str_getcsv(array_shift($lines));
        $headers  = array_map('strtolower', array_map('trim', $headers));
        $products = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            $values = str_getcsv($line);
            $row    = array_combine($headers, array_pad($values, count($headers), ''));

            $link = $row['link'] ?? $row['url'] ?? $row['product_url'] ?? '';
            if (!$link || !filter_var($link, FILTER_VALIDATE_URL)) continue;

            $price     = $this->parsePrice($row['price'] ?? '');
            $salePrice = $this->parsePrice($row['sale_price'] ?? $row['sale price'] ?? '');
            $marginRaw = (float) ($row['margin'] ?? $row['marge'] ?? 0);

            $products[] = [
                'url'          => rtrim($link, '/'),
                'sku'          => $row['id'] ?? $row['sku'] ?? $row['mpn'] ?? '',
                'title'        => $row['title'] ?? '',
                'price'        => $price,
                'sale_price'   => $salePrice,
                'margin'       => $marginRaw > 1 ? $marginRaw / 100 : ($marginRaw ?: null),
                'margin_amount'=> null,
                'brand'        => $row['brand'] ?? '',
                'category'     => $row['product_type'] ?? $row['category'] ?? '',
                'availability' => $row['availability'] ?? '',
            ];
        }

        return $products;
    }

    private function detectMargin(\SimpleXMLElement $g, \SimpleXMLElement $item, ?float $price, ?float $salePrice): array
    {
        // Expliciete margin-velden (Channable custom export)
        foreach (['margin', 'marge', 'margin_percentage', 'margin_perc'] as $field) {
            $val = (float) ($g->$field ?? $item->$field ?? 0);
            if ($val > 0) {
                $ratio = $val > 1 ? $val / 100 : $val;
                return ['ratio' => $ratio, 'amount' => $price ? round($price * $ratio, 2) : null];
            }
        }

        // Custom labels (Channable gebruikt deze vaak voor marge-buckets)
        for ($i = 0; $i <= 4; $i++) {
            $label = (string) ($g->{"custom_label_{$i}"} ?? '');
            if (preg_match('/^(\d+(?:[.,]\d+)?)\s*%?$/', $label, $m)) {
                $val   = (float) str_replace(',', '.', $m[1]);
                $ratio = $val > 1 ? $val / 100 : $val;
                return ['ratio' => $ratio, 'amount' => $price ? round($price * $ratio, 2) : null];
            }
        }

        // Bereken uit prijs vs verkoopprijs als fallback
        if ($price && $salePrice && $price > 0) {
            $ratio = ($price - $salePrice) / $price;
            if ($ratio > 0 && $ratio < 1) {
                return ['ratio' => round($ratio, 4), 'amount' => round($price - $salePrice, 2)];
            }
        }

        return ['ratio' => null, 'amount' => null];
    }

    private function parsePrice(string $raw): ?float
    {
        $raw = trim(preg_replace('/[^\d.,]/', '', $raw));
        if (!$raw) return null;

        // "1.234,56" → 1234.56
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $raw)) {
            $raw = str_replace(['.', ','], ['', '.'], $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        $val = (float) $raw;
        return $val > 0 ? $val : null;
    }

    private function saveProducts(array $products, Site $site): void
    {
        $new      = 0;
        $updated  = 0;
        $linked   = 0;
        $pagesSet = 0;

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        foreach ($products as $data) {
            // Zoek bestaande pagina op URL
            $page = Page::where('site_id', $site->id)
                ->where('url', $data['url'])
                ->first();

            // Maak pagina aan als die nog niet bestaat
            if (!$page) {
                $path = parse_url($data['url'], PHP_URL_PATH) ?? '/';
                $page = Page::create([
                    'site_id'   => $site->id,
                    'url'       => $data['url'],
                    'path'      => $path,
                    'type'      => 'product',
                    'title'     => $data['title'] ?: null,
                    'is_active' => true,
                ]);
                $linked++;
            } elseif ($page->type !== 'product') {
                // Corrigeer type op basis van Channable (authoritative bron)
                $page->update(['type' => 'product']);
                $pagesSet++;
            }

            // Sla Channable data op / update
            $existing = ChannableData::where('site_id', $site->id)
                ->where('url', $data['url'])
                ->first();

            $payload = [
                'site_id'        => $site->id,
                'page_id'        => $page->id,
                'url'            => $data['url'],
                'sku'            => $data['sku'] ?: null,
                'title'          => $data['title'],
                'price'          => $data['price'],
                'sale_price'     => $data['sale_price'],
                'margin'         => $data['margin'],
                'margin_amount'  => $data['margin_amount'],
                'brand'          => $data['brand'] ?: null,
                'category'       => $data['category'] ?: null,
                'availability'   => $data['availability'] ?: null,
                'is_active'      => true,
                'feed_updated_at'=> now(),
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                ChannableData::create($payload);
                $new++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("  Klaar: {$new} nieuw, {$updated} bijgewerkt" .
            ($linked   ? ", {$linked} pagina's aangemaakt" : '') .
            ($pagesSet ? ", {$pagesSet} types gecorrigeerd naar 'product'" : '') . ".");
    }

    private function showPreview(array $products): void
    {
        $sample = array_slice($products, 0, 10);
        $this->table(
            ['URL', 'SKU', 'Prijs', 'Marge', 'Categorie'],
            array_map(fn($p) => [
                \Illuminate\Support\Str::limit($p['url'], 60),
                $p['sku'],
                $p['price'] ? '€' . number_format($p['price'], 2) : '—',
                $p['margin'] ? round($p['margin'] * 100, 1) . '%' : '—',
                \Illuminate\Support\Str::limit($p['category'], 40),
            ], $sample)
        );
        $this->line("  (toont 10 van " . count($products) . " producten)");
    }
}
