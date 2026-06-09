<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageMetricGeo;
use App\Models\Site;
use App\Models\SiteGeoCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScanGeo extends Command
{
    protected $signature = 'scan:geo
                            {--site= : Domein van een specifieke site}
                            {--limit=50 : Max pagina\'s per run}
                            {--type= : Filter op paginatype: product, category, content}
                            {--force : Herscoren van al gescande pagina\'s}
                            {--robots-only : Alleen robots.txt/llms.txt checken}';

    protected $description = 'Beoordeel pagina\'s op GEO/LLM-zichtbaarheid';

    // Vergelijk/contrast woorden die informatie-dichtheid aangeven
    private array $comparisonKeywords = [
        'versus', ' vs ', 'vergelijk', 'verschil', 'beter dan', 'voordeel',
        'nadeel', 'in tegenstelling', 'verschilt', 'alternatief', 'kenmerken',
        'specificaties', 'voor- en nadelen', 'of kies', 'welke is',
    ];

    // Vraagwoorden voor question-formatted blokken
    private array $questionStarters = [
        'wat ', 'hoe ', 'waarom ', 'welke ', 'wanneer ', 'wie ', 'waar ',
        'is ', 'kan ', 'mag ', 'moet ', 'heeft ', 'zijn ', 'kost ',
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
            // Altijd robots/llms checken
            $this->checkSiteRobots($site);

            if (!$this->option('robots-only')) {
                $this->scanPages($site);
            }
        }

        return 0;
    }

    // -------------------------------------------------------------------------
    // Site-level: robots.txt + llms.txt
    // -------------------------------------------------------------------------

    private function checkSiteRobots(Site $site): void
    {
        $this->info("Site-check: {$site->name}");

        $robotsUrl = "https://{$site->domain}/robots.txt";
        $llmsTxtUrl = "https://{$site->domain}/llms.txt";

        $robotsTxt = null;
        try {
            $r = Http::timeout(10)->get($robotsUrl);
            $robotsTxt = $r->successful() ? $r->body() : null;
        } catch (\Exception) {}

        $hasLlmsTxt = false;
        try {
            $r = Http::timeout(10)->get($llmsTxtUrl);
            $hasLlmsTxt = $r->successful() && strlen($r->body()) > 10;
        } catch (\Exception) {}

        $botStatuses = [];
        foreach (SiteGeoCheck::$knownBots as $bot => $info) {
            $botStatuses[$bot] = $this->parseBotStatus($robotsTxt ?? '', $bot);
        }

        SiteGeoCheck::updateOrCreate(
            ['site_id' => $site->id],
            [
                'checked_at'  => now(),
                'robots_txt'  => $robotsTxt,
                'has_llms_txt'=> $hasLlmsTxt,
                'llm_bots'    => $botStatuses,
            ]
        );

        $allowed  = collect($botStatuses)->filter(fn($s) => $s === 'allowed')->count();
        $blocked  = collect($botStatuses)->filter(fn($s) => $s === 'blocked')->count();
        $unknown  = collect($botStatuses)->filter(fn($s) => $s === 'unknown')->count();

        $this->line("  robots.txt: {$allowed} bots toegestaan, {$blocked} geblokkeerd, {$unknown} niet vermeld");
        $this->line("  llms.txt: " . ($hasLlmsTxt ? '✓ aanwezig' : '✗ niet gevonden'));
    }

    private function parseBotStatus(string $robotsTxt, string $botName): string
    {
        if (empty($robotsTxt)) return 'unknown';

        $lines        = explode("\n", $robotsTxt);
        $currentAgent = null;
        $agentRules   = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'user-agent:') === 0) {
                $currentAgent = strtolower(trim(substr($line, 11)));
            } elseif ($currentAgent !== null && stripos($line, 'disallow:') === 0) {
                $agentRules[$currentAgent][] = ['type' => 'disallow', 'path' => trim(substr($line, 9))];
            } elseif ($currentAgent !== null && stripos($line, 'allow:') === 0) {
                $agentRules[$currentAgent][] = ['type' => 'allow', 'path' => trim(substr($line, 6))];
            }
        }

        $botKey = strtolower($botName);

        // Exacte match
        if (isset($agentRules[$botKey])) {
            foreach ($agentRules[$botKey] as $rule) {
                if ($rule['type'] === 'disallow' && $rule['path'] === '/') return 'blocked';
                if ($rule['type'] === 'allow' && $rule['path'] === '/') return 'allowed';
            }
            return 'allowed'; // vermeld maar geen / disallow
        }

        // Wildcard *
        if (isset($agentRules['*'])) {
            foreach ($agentRules['*'] as $rule) {
                if ($rule['type'] === 'disallow' && $rule['path'] === '/') return 'blocked-by-wildcard';
            }
        }

        return 'unknown'; // niet vermeld = standaard toegestaan
    }

    // -------------------------------------------------------------------------
    // Pagina-level: GEO score
    // -------------------------------------------------------------------------

    private function scanPages(Site $site): void
    {
        $query = Page::where('site_id', $site->id)
            ->where('is_active', true)
            ->when($this->option('type'), fn($q) => $q->where('type', $this->option('type')));

        if (!$this->option('force')) {
            $recentIds = PageMetricGeo::whereIn('page_id', $query->pluck('id'))
                ->where('scored_at', '>=', now()->subDays(14))
                ->pluck('page_id');
            $query->whereNotIn('id', $recentIds);
        }

        $pages = $query->orderByRaw('(SELECT MAX(scored_at) FROM page_metrics_geo WHERE page_id = pages.id) ASC NULLS FIRST')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($pages->isEmpty()) {
            $this->line("  Geen pagina's om te scannen.");
            return;
        }

        $this->line("  {$pages->count()} pagina's in wachtrij");

        $bar    = $this->output->createProgressBar($pages->count());
        $scored = 0;
        $failed = 0;

        $bar->start();

        foreach ($pages as $page) {
            $result = $this->analyzePage($page->url);

            if ($result) {
                PageMetricGeo::updateOrCreate(
                    ['page_id' => $page->id],
                    array_merge($result, ['scored_at' => now()])
                );
                $scored++;
            } else {
                $failed++;
            }

            $bar->advance();
            usleep(500000); // 0.5s tussen requests
        }

        $bar->finish();
        $this->newLine();
        $this->info("  {$scored} gescoord" . ($failed ? ", {$failed} mislukt" : '') . '.');
    }

    private function analyzePage(string $url): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'LC-Dashboard-GEO-Bot/1.0'])
                ->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();
        } catch (\Exception) {
            return null;
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        return $this->score($html, $dom, $xpath);
    }

    private function score(string $html, \DOMDocument $dom, \DOMXPath $xpath): array
    {
        $issues = [];
        $points = 0;

        // -------------------------------------------------------
        // BLOK A: TECHNISCH / BRONCODE (25 pts)
        // Stap 2 (bots) + Stap 4 (EEAT schema) + Stap 7 (structuur)
        // -------------------------------------------------------

        $jsonLdNodes          = $xpath->query('//script[@type="application/ld+json"]');
        $hasJsonLd            = $jsonLdNodes && $jsonLdNodes->length > 0;
        $jsonLdTypes          = [];
        $hasFaqSchema         = false;
        $hasProductSchema     = false;
        $hasBreadcrumb        = false;
        $hasOrganizationSchema= false;
        $hasAuthorSchema      = false;

        if ($hasJsonLd) {
            $points += 5; // JSON-LD aanwezig
            foreach ($jsonLdNodes as $node) {
                try {
                    $data  = json_decode($node->textContent, true);
                    if (!$data) continue;
                    $items = isset($data[0]) ? $data : [$data];
                    foreach ($items as $item) {
                        $type = $item['@type'] ?? null;
                        if (!$type) continue;
                        $jsonLdTypes[] = $type;
                        if (in_array($type, ['FAQPage', 'QAPage']))       $hasFaqSchema = true;
                        if (in_array($type, ['Product', 'ItemPage']))     $hasProductSchema = true;
                        if ($type === 'BreadcrumbList')                   $hasBreadcrumb = true;
                        if (in_array($type, ['Organization', 'LocalBusiness', 'WebSite'])) $hasOrganizationSchema = true;
                        if (in_array($type, ['Person', 'Author']))        $hasAuthorSchema = true;
                        // author embedded in Article/Product
                        if (!empty($item['author']['@type']) && in_array($item['author']['@type'], ['Person', 'Organization'])) {
                            $hasAuthorSchema = true;
                        }
                    }
                } catch (\Exception) {}
            }
            if ($hasFaqSchema)          $points += 5;
            if ($hasProductSchema)      $points += 3;
            if ($hasBreadcrumb)         $points += 2;
            if ($hasOrganizationSchema) $points += 4; // EEAT: organisatie-identiteit
            if ($hasAuthorSchema)       $points += 3; // EEAT: auteur/expertise
        } else {
            $issues[] = 'Geen JSON-LD structured data';
        }

        // Open Graph (3 pts) — stap 2: zichtbaar voor AI-platforms
        $ogTitle = $xpath->query('//meta[@property="og:title"]');
        $hasOg   = $ogTitle && $ogTitle->length > 0;
        if ($hasOg) {
            $points += 3;
        } else {
            $issues[] = 'Geen Open Graph tags';
        }

        // -------------------------------------------------------
        // BLOK B: METADATA & STRUCTUUR (15 pts)
        // Stap 7 (structuur), stap 5 (direct antwoord)
        // -------------------------------------------------------

        // Canonical (2 pts)
        $canonical    = $xpath->query('//link[@rel="canonical"]');
        $hasCanonical = $canonical && $canonical->length > 0;
        if ($hasCanonical) $points += 2;

        // Meta description 50+ chars (3 pts)
        $metaDesc    = $xpath->query('//meta[@name="description"]');
        $hasMetaDesc = $metaDesc && $metaDesc->length > 0 &&
                       strlen($metaDesc->item(0)->getAttribute('content') ?? '') > 50;
        if ($hasMetaDesc) {
            $points += 3;
        } else {
            $issues[] = 'Meta description ontbreekt of te kort';
        }

        // H1 aanwezig (3 pts) — stap 7: structuur
        $h1Nodes  = $xpath->query('//h1');
        $h1Count  = $h1Nodes ? $h1Nodes->length : 0;
        $hasH1    = $h1Count === 1;
        if ($hasH1) {
            $points += 3;
        } elseif ($h1Count === 0) {
            $issues[] = 'Geen H1 aanwezig';
        } else {
            $issues[] = "Meerdere H1's gevonden ({$h1Count}x) — gebruik er precies één";
        }

        // Inhoudsopgave / table of contents (3 pts) — stap 7
        $tocNodes = $xpath->query(
            '//*[contains(@class,"toc") or contains(@class,"table-of-contents") or contains(@id,"toc")]
            | //nav[contains(@aria-label,"inhoud") or contains(@aria-label,"contents")]'
        );
        $hasToC = $tocNodes && $tocNodes->length > 0;
        if ($hasToC) {
            $points += 3;
        }

        // Lijsten/tabellen: herkauwbare content (4 pts) — stap 6
        $listNodes  = $xpath->query('//main//ul[count(li)>=3] | //main//ol[count(li)>=3] | //article//ul[count(li)>=3] | //article//ol[count(li)>=3]');
        $tableNodes = $xpath->query('//main//table | //article//table');
        $hasListContent = ($listNodes && $listNodes->length > 0) || ($tableNodes && $tableNodes->length > 0);
        if ($hasListContent) {
            $points += 4;
        } else {
            $issues[] = 'Geen lijsten of tabellen (herkauwbare content voor LLMs)';
        }

        // -------------------------------------------------------
        // BLOK C: CONTENT ARCHITECTUUR (45 pts)
        // Stap 3 (uniek), stap 4 (EEAT citaten), stap 5 (direct antwoord), stap 6 (herkauwbaar)
        // -------------------------------------------------------

        // Citeerbare intro ≥ 35 woorden (12 pts) — stap 5: direct antwoord
        $firstPara       = $xpath->query('//main//p | //article//p | //div[@class]//p');
        $introWords      = 0;
        $hasCitableIntro = false;

        if ($firstPara && $firstPara->length > 0) {
            for ($i = 0; $i < min(3, $firstPara->length); $i++) {
                $text = trim($firstPara->item($i)->textContent ?? '');
                $wc   = str_word_count($text);
                if ($wc >= 35) {
                    $introWords      = $wc;
                    $hasCitableIntro = true;
                    break;
                }
            }
        }
        if ($hasCitableIntro) {
            $points += 12;
        } else {
            $issues[] = 'Geen citeerbare intro (eerste alinea < 35 woorden)';
        }

        // Vraagkoppen H2/H3 (10 pts) — stap 5 & stap 7
        $headings             = $xpath->query('//h2 | //h3');
        $questionHeadingCount = 0;
        if ($headings) {
            foreach ($headings as $heading) {
                $text = strtolower(trim($heading->textContent ?? ''));
                $isQ  = str_ends_with($text, '?');
                if (!$isQ) {
                    foreach ($this->questionStarters as $s) {
                        if (str_starts_with($text, $s)) { $isQ = true; break; }
                    }
                }
                if ($isQ) $questionHeadingCount++;
            }
        }
        if ($questionHeadingCount >= 2) {
            $points += 10;
        } elseif ($questionHeadingCount === 1) {
            $points += 5;
            $issues[] = 'Slechts 1 vraagkop (streef naar 2+: "Wat is...?", "Hoe werkt...?")';
        } else {
            $issues[] = 'Geen vraagkoppen (H2/H3 als vraag) — essentieel voor LLM-citatie';
        }

        // FAQ-blok: schema of <details>/<summary> (8 pts) — stap 5
        $detailsNodes = $xpath->query('//details | //summary');
        $hasFaqBlock  = $hasFaqSchema || ($detailsNodes && $detailsNodes->length > 0);
        if ($hasFaqBlock) {
            $points += 8;
        } else {
            $issues[] = 'Geen FAQ-schema of uitklapbare Q&A blokken';
        }

        // Vergelijkende/onderscheidende copy (8 pts) — stap 3 & 5
        $bodyText      = strtolower($dom->textContent ?? '');
        $hasComparison = false;
        foreach ($this->comparisonKeywords as $kw) {
            if (str_contains($bodyText, $kw)) { $hasComparison = true; break; }
        }
        if ($hasComparison) {
            $points += 8;
        } else {
            $issues[] = 'Geen vergelijkende taal (vs, verschil, voordelen, alternatieven)';
        }

        // Externe links / citaten (7 pts) — stap 4: EEAT bronvermelding
        $extLinks      = $xpath->query('//main//a[starts-with(@href,"http")] | //article//a[starts-with(@href,"http")]');
        $extLinkCount  = $extLinks ? $extLinks->length : 0;
        if ($extLinkCount >= 3) {
            $points += 7;
        } elseif ($extLinkCount >= 1) {
            $points += 3;
            $issues[] = 'Weinig externe bronlinks — voeg citaten/studies toe (EEAT)';
        } else {
            $issues[] = 'Geen externe bronlinks — citeer bronnen voor EEAT-signaal';
        }

        // -------------------------------------------------------
        // BLOK D: VERSHEID (15 pts)
        // Stap 8: recency
        // -------------------------------------------------------

        $dateModified  = null;
        $freshnessDays = null;

        if ($hasJsonLd) {
            foreach ($jsonLdNodes as $node) {
                try {
                    $data  = json_decode($node->textContent, true);
                    $items = isset($data[0]) ? $data : [$data];
                    foreach ($items as $item) {
                        if (!empty($item['dateModified'])) {
                            $dateModified  = \Carbon\Carbon::parse($item['dateModified'])->toDateString();
                            $freshnessDays = (int) \Carbon\Carbon::parse($item['dateModified'])->diffInDays(now());
                            break 2;
                        }
                    }
                } catch (\Exception) {}
            }
        }

        if (!$dateModified) {
            $metaMod = $xpath->query('//meta[@name="last-modified"] | //meta[@http-equiv="last-modified"]');
            if ($metaMod && $metaMod->length > 0) {
                try {
                    $dateModified  = \Carbon\Carbon::parse($metaMod->item(0)->getAttribute('content'))->toDateString();
                    $freshnessDays = (int) \Carbon\Carbon::parse($dateModified)->diffInDays(now());
                } catch (\Exception) {}
            }
        }

        if ($freshnessDays !== null) {
            if ($freshnessDays <= 30)      $points += 15;
            elseif ($freshnessDays <= 90)  $points += 10;
            elseif ($freshnessDays <= 180) $points += 5;
            else {
                $points += 1;
                $issues[] = "Pagina al {$freshnessDays} dagen niet bijgewerkt — update content + dateModified";
            }
        } else {
            $issues[] = 'Geen dateModified in JSON-LD — voeg toe voor versheidsignaal';
        }

        return [
            // Broncode
            'has_json_ld'             => $hasJsonLd,
            'json_ld_types'           => array_values(array_unique($jsonLdTypes)) ?: null,
            'has_faq_schema'          => $hasFaqSchema,
            'has_product_schema'      => $hasProductSchema,
            'has_breadcrumb_schema'   => $hasBreadcrumb,
            'has_organization_schema' => $hasOrganizationSchema,
            'has_author_schema'       => $hasAuthorSchema,
            'has_open_graph'          => $hasOg,
            'has_canonical'           => $hasCanonical,
            'has_meta_description'    => $hasMetaDesc,
            // Structuur
            'has_h1'                  => $hasH1,
            'h1_count'                => $h1Count,
            'has_table_of_contents'   => $hasToC,
            'has_list_content'        => $hasListContent,
            // Content
            'intro_word_count'        => $introWords ?: null,
            'has_citable_intro'       => $hasCitableIntro,
            'question_heading_count'  => $questionHeadingCount,
            'has_faq_block'           => $hasFaqBlock,
            'has_comparison_content'  => $hasComparison,
            'external_link_count'     => $extLinkCount,
            // Versheid
            'date_modified'           => $dateModified,
            'freshness_days'          => $freshnessDays,
            // Score
            'geo_score'               => min(100, $points),
            'issues'                  => $issues,
        ];
    }
}
