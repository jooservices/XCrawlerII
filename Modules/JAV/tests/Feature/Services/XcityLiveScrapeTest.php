<?php

namespace Modules\JAV\Tests\Feature\Services;

use Modules\JAV\Services\Xcity\XcityDetailAdapter;
use Modules\JAV\Services\Xcity\XcityListAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class XcityLiveScrapeTest extends TestCase
{
    private const BASE_URL = 'https://xxx.xcity.jp';

    public function test_live_kana_sub_pages_and_random_profiles_can_be_extracted_without_missing_profile_labels(): void
    {
        if (getenv('LIVE_XCITY_TESTS') !== '1') {
            $this->markTestSkipped('Set LIVE_XCITY_TESTS=1 to run live XCITY integration checks.');
        }

        mt_srand(20260214);

        $rootHtml = $this->curl(self::BASE_URL.'/idol/');
        $seedUrls = $this->extractKanaIniSeeds($rootHtml);

        $this->assertNotEmpty($seedUrls, 'No kana+ini seeds found from XCITY root page.');

        foreach ($seedUrls as $seedUrl) {
            $firstHtml = $this->curl($seedUrl);
            $firstPage = (new XcityListAdapter(new Crawler($firstHtml)))->page();

            $this->assertNotEmpty($firstPage->idols->toArray(), "No idols found on seed page: {$seedUrl}");

            // Current/next page behavior for each kana+ini seed.
            $seedParams = $this->queryParams($seedUrl);
            if ($firstPage->nextUrl !== null) {
                $nextParams = $this->queryParams($firstPage->nextUrl);
                $this->assertSame($seedParams['kana'] ?? null, $nextParams['kana'] ?? null, "Next page kana mismatch for {$seedUrl}");
                $this->assertSame($seedParams['ini'] ?? null, $nextParams['ini'] ?? null, "Next page ini mismatch for {$seedUrl}");
                $this->assertArrayHasKey('page', $nextParams, "Next page param missing for {$seedUrl}");

                $nextHtml = $this->curl($firstPage->nextUrl);
                $nextPage = (new XcityListAdapter(new Crawler($nextHtml)))->page();
                $this->assertNotNull($nextPage->idols, "Failed to parse next page idols for {$seedUrl}");
            }

            $sampleIdols = $this->randomSample($firstPage->idols->toArray(), 2);
            foreach ($sampleIdols as $idol) {
                $detailHtml = $this->curl($idol->detailUrl);
                $detailAdapter = new XcityDetailAdapter(new Crawler($detailHtml));
                $profile = $detailAdapter->profile();

                $rawFromHtml = $this->extractRawProfileFromHtml($detailHtml);
                $this->assertNotEmpty($rawFromHtml, "No profile labels found on detail page: {$idol->detailUrl}");

                // No missing profile labels: every non-empty raw label/value from HTML must exist in parsed raw_fields.
                foreach ($rawFromHtml as $label => $value) {
                    $this->assertArrayHasKey($label, $profile['raw_fields'], "Missing parsed raw profile label '{$label}' at {$idol->detailUrl}");
                    $this->assertSame($value, $profile['raw_fields'][$label], "Profile value mismatch for '{$label}' at {$idol->detailUrl}");
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function extractKanaIniSeeds(string $html): array
    {
        $crawler = new Crawler($html);
        $urls = [];

        $crawler->filter('a[href*="/idol/?kana="][href*="ini="]')->each(function (Crawler $node) use (&$urls): void {
            $href = trim((string) $node->attr('href'));
            if ($href === '') {
                return;
            }

            $url = $this->absoluteUrl($href);
            $params = $this->queryParams($url);

            if (($params['kana'] ?? '') === '' || ($params['ini'] ?? '') === '') {
                return;
            }

            $urls[$url] = $url;
        });

        ksort($urls);

        return array_values($urls);
    }

    /**
     * @return array<string, string>
     */
    private function extractRawProfileFromHtml(string $html): array
    {
        $crawler = new Crawler($html);
        $fields = [];

        $crawler->filter('#avidolDetails dl.profile dd')->each(function (Crawler $dd) use (&$fields): void {
            $labelNode = $dd->filter('span.koumoku');
            if ($labelNode->count() === 0) {
                return;
            }

            $label = trim($labelNode->text(''));
            if ($label === '' || str_contains($label, '★')) {
                return;
            }

            $valueHtml = $dd->html('');
            $valueHtml = preg_replace('/<span[^>]*class="koumoku"[^>]*>.*?<\/span>/u', '', $valueHtml, 1);
            $value = html_entity_decode(strip_tags((string) $valueHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = trim((string) preg_replace('/\s+/u', ' ', $value));

            if ($value === '') {
                return;
            }

            $fields[$label] = $value;
        });

        return $fields;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function randomSample(array $items, int $max): array
    {
        if ($items === []) {
            return [];
        }

        if (count($items) <= $max) {
            return $items;
        }

        $keys = array_rand($items, $max);
        if (! is_array($keys)) {
            return [$items[$keys]];
        }

        $sample = [];
        foreach ($keys as $key) {
            $sample[] = $items[$key];
        }

        return $sample;
    }

    /**
     * @return array<string, string>
     */
    private function queryParams(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return self::BASE_URL.'/'.ltrim($url, '/');
    }

    private function curl(string $url): string
    {
        $command = sprintf(
            'curl -fsSL --retry 2 --connect-timeout 15 --max-time 45 %s',
            escapeshellarg($url)
        );

        $output = shell_exec($command);
        $this->assertIsString($output, "curl failed for {$url}");
        $this->assertNotSame('', $output, "empty response from {$url}");

        return $output;
    }
}
