<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Core\Facades\Config;
use Modules\JAV\Models\Actor;
use Modules\JAV\Services\ActorProfileUpsertService;
use Modules\JAV\Services\Clients\XcityClient;
use Modules\JAV\Services\Xcity\XcityDetailAdapter;
use Modules\JAV\Services\Xcity\XcityListAdapter;
use Symfony\Component\DomCrawler\Crawler;

class XcityIdolService
{
    private const BASE_PATH = '/idol/';

    private const BASE_URL = 'https://xxx.xcity.jp';

    public function __construct(
        protected XcityClient $client,
        protected ActorProfileUpsertService $profileUpsertService
    ) {}

    /**
     * @return array<string, string>
     */
    public function seedKanaUrls(): array
    {
        $cachedSeeds = $this->readSeedCache();
        if ($cachedSeeds !== []) {
            return $cachedSeeds;
        }

        $response = $this->client->get(self::BASE_PATH);
        $html = $response->toPsrResponse()->getBody()->getContents();
        $crawler = new Crawler($html);

        $rootKanaUrls = [];
        $seedUrls = [];

        $crawler->filter('a[href*="/idol/?"]')->each(function (Crawler $node) use (&$rootKanaUrls, &$seedUrls) {
            $href = trim((string) $node->attr('href'));
            if ($href === '') {
                return;
            }

            $url = $this->toAbsoluteUrl($href);
            $query = $this->queryParams($url);

            if (!isset($query['kana']) || trim((string) $query['kana']) === '') {
                return;
            }

            $kana = trim((string) $query['kana']);
            $ini = trim((string) ($query['ini'] ?? ''));

            if ($ini !== '') {
                $seedUrls[$url] = $url;

                return;
            }

            $rootKanaUrls["{$kana}|{$url}"] = $url;
        });

        foreach ($rootKanaUrls as $kanaUrl) {
            $subSeeds = $this->discoverIniUrls($kanaUrl);

            if ($subSeeds === []) {
                $seedUrls[$kanaUrl] = $kanaUrl;

                continue;
            }

            foreach ($subSeeds as $subUrl) {
                $seedUrls[$subUrl] = $subUrl;
            }
        }

        $urls = [];
        foreach ($seedUrls as $seedUrl) {
            $urls[$this->seedKeyFromUrl($seedUrl)] = $seedUrl;
        }

        if ($urls === []) {
            // Fallback to a basic seed to keep sync alive when top-page selector changes.
            $fallback = self::BASE_URL . self::BASE_PATH;
            $urls[$this->seedKeyFromUrl($fallback)] = $fallback;
        }

        ksort($urls);
        $this->writeSeedCache($urls);

        return $urls;
    }

    public function syncKanaPage(string $seedKey, string $seedUrl): int
    {
        Config::set('xcity', $this->runningKey($seedKey), '1');

        try {
            $pageUrl = (string) Config::get('xcity', $this->nextUrlKey($seedKey), $seedUrl);
            $page = $this->listPage($pageUrl);

            foreach ($page->idols as $idol) {
                $detail = $this->idolDetail($idol->detailUrl);
                $this->linkActor(
                    xcityId: $idol->xcityId,
                    name: $idol->name,
                    detailUrl: $idol->detailUrl,
                    coverImage: $idol->coverImage,
                    detail: $detail
                );
            }

            if ($page->nextUrl !== null) {
                Config::set('xcity', $this->nextUrlKey($seedKey), $page->nextUrl);
                Config::set('xcity', $this->pageKey($seedKey), (string) ((int) Config::get('xcity', $this->pageKey($seedKey), 1) + 1));
            } else {
                // Same wrap-around behavior as existing provider page cursors.
                Config::set('xcity', $this->nextUrlKey($seedKey), $seedUrl);
                Config::set('xcity', $this->pageKey($seedKey), '1');
                Config::set('xcity', $this->completedAtKey($seedKey), Carbon::now()->toDateTimeString());
            }

            return $page->idols->count();
        } finally {
            Config::set('xcity', $this->runningKey($seedKey), '0');
        }
    }

    public function listPage(string $url): \Modules\JAV\Dtos\XcityIdolPage
    {
        $response = $this->client->get($url);
        $html = $response->toPsrResponse()->getBody()->getContents();
        $crawler = new Crawler($html);

        return (new XcityListAdapter($crawler))->page();
    }

    /**
     * @return array{
     *     name: ?string,
     *     cover_image: ?string,
     *     fields: array<string, string>,
     *     raw_fields: array<string, string>
     * }
     */
    public function idolDetail(string $url): array
    {
        $response = $this->client->get($url);
        $html = $response->toPsrResponse()->getBody()->getContents();
        $crawler = new Crawler($html);

        return (new XcityDetailAdapter($crawler))->profile();
    }

    /**
     * @param  array<string, string>  $seedUrls
     * @return Collection<int, array{seed_key: string, seed_url: string}>
     */
    public function pickSeedsForDispatch(array $seedUrls, int $concurrency): Collection
    {
        if ($seedUrls === []) {
            return collect();
        }

        $concurrency = max(1, $concurrency);
        $keys = array_keys($seedUrls);
        $total = count($keys);
        $cursor = (int) Config::get('xcity', 'cursor', 0);

        $selected = collect();

        for ($offset = 0; $offset < $total; $offset++) {
            if ($selected->count() >= $concurrency) {
                break;
            }

            $index = ($cursor + $offset) % $total;
            $seedKey = $keys[$index];
            $running = (string) Config::get('xcity', $this->runningKey($seedKey), '0');

            if ($running === '1') {
                continue;
            }

            $selected->push([
                'seed_key' => $seedKey,
                'seed_url' => $seedUrls[$seedKey],
            ]);
        }

        Config::set('xcity', 'cursor', (string) (($cursor + max(1, $selected->count())) % $total));

        return $selected;
    }

    /**
     * @param  array{
     *     name: ?string,
     *     cover_image: ?string,
     *     fields: array<string, string>,
     *     raw_fields: array<string, string>
     * }  $detail
     */
    private function linkActor(string $xcityId, string $name, string $detailUrl, ?string $coverImage, array $detail): void
    {
        $normalizedName = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
        if ($normalizedName === '') {
            return;
        }

        $actor = Actor::query()->where('xcity_id', $xcityId)->first();

        if ($actor === null) {
            $actor = Actor::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedName)])
                ->first();
        }

        if ($actor === null) {
            $actor = Actor::create(['name' => $normalizedName]);
        }

        $actor->xcity_id = $xcityId;
        $actor->xcity_url = $detailUrl;
        $resolvedCover = $detail['cover_image'] ?? $coverImage;
        if (is_string($resolvedCover) && $resolvedCover !== '') {
            $actor->xcity_cover = $resolvedCover;
        }

        $fields = $detail['fields'] ?? [];
        if (is_array($fields)) {
            $normalizedBloodType = $this->normalizeBloodType($fields['blood_type'] ?? null);

            $actor->xcity_birth_date = $this->normalizeBirthDate($fields['birth_date'] ?? null);
            $actor->xcity_blood_type = $normalizedBloodType;
            $actor->xcity_city_of_birth = $fields['city_of_birth'] ?? null;
            $actor->xcity_height = $fields['height'] ?? null;
            $actor->xcity_size = $fields['size'] ?? null;
            $actor->xcity_hobby = $fields['hobby'] ?? null;
            $actor->xcity_special_skill = $fields['special_skill'] ?? null;
            $actor->xcity_other = $fields['other'] ?? null;
            if (array_key_exists('blood_type', $fields)) {
                $fields['blood_type'] = $normalizedBloodType;
            }
            $actor->xcity_profile = [
                'mapped' => $fields,
                'raw' => is_array($detail['raw_fields'] ?? null) ? $detail['raw_fields'] : [],
            ];
        }

        $actor->xcity_synced_at = Carbon::now();
        $shouldIndex = $actor->isDirty();

        Actor::withoutSyncingToSearch(function () use ($actor): void {
            $actor->save();
        });

        $attributes = $this->buildProfileAttributes(
            is_array($fields) ? $fields : [],
            is_array($detail['raw_fields'] ?? null) ? $detail['raw_fields'] : []
        );
        $this->profileUpsertService->syncSource(
            actor: $actor,
            source: 'xcity',
            sourceData: [
                'source_actor_id' => $xcityId,
                'source_url' => $detailUrl,
                'source_cover' => $actor->xcity_cover,
                'payload' => is_array($actor->xcity_profile) ? $actor->xcity_profile : null,
                'fetched_at' => Carbon::now(),
                'synced_at' => $actor->xcity_synced_at,
            ],
            attributes: $attributes,
            isPrimary: true
        );

        if ($shouldIndex) {
            try {
                $actor->searchable();
            } catch (\Throwable $exception) {
                Log::warning('Failed to sync actor to search index after XCITY update', [
                    'actor_id' => $actor->id,
                    'xcity_id' => $xcityId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function toAbsoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL . $url;
        }

        return self::BASE_URL . '/' . ltrim($url, '/');
    }

    private function seedKeyFromUrl(string $url): string
    {
        return substr(sha1($url), 0, 12);
    }

    /**
     * @return array<string, string>
     */
    private function discoverIniUrls(string $kanaUrl): array
    {
        $rootQuery = $this->queryParams($kanaUrl);
        $kana = trim((string) ($rootQuery['kana'] ?? ''));
        if ($kana === '') {
            return [];
        }

        $response = $this->client->get($kanaUrl);
        $html = $response->toPsrResponse()->getBody()->getContents();
        $crawler = new Crawler($html);

        $urls = [];
        $crawler->filter('a[href*="/idol/?"]')->each(function (Crawler $node) use (&$urls, $kana) {
            $href = trim((string) $node->attr('href'));
            if ($href === '') {
                return;
            }

            $url = $this->toAbsoluteUrl($href);
            $query = $this->queryParams($url);

            if (!isset($query['kana'], $query['ini'])) {
                return;
            }

            $urlKana = trim((string) $query['kana']);
            $urlIni = trim((string) $query['ini']);

            if ($urlKana !== $kana || $urlIni === '') {
                return;
            }

            $urls[$url] = $url;
        });

        ksort($urls);

        return $urls;
    }

    /**
     * @return array<string, string>
     */
    private function queryParams(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? $params : [];
    }

    /**
     * @return array<string, string>
     */
    private function readSeedCache(): array
    {
        $cachedJson = (string) Config::get('xcity', 'seed_urls_json', '');
        $cachedAt = (string) Config::get('xcity', 'seed_urls_cached_at', '');

        if ($cachedJson === '' || $cachedAt === '') {
            return [];
        }

        try {
            $fresh = Carbon::parse($cachedAt)->gt(Carbon::now()->subHours(12));
        } catch (\Throwable) {
            $fresh = false;
        }

        if (!$fresh) {
            return [];
        }

        $decoded = json_decode($cachedJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $urls = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_string($value) && $value !== '') {
                $urls[$key] = $value;
            }
        }

        return $urls;
    }

    /**
     * @param  array<string, string>  $urls
     */
    private function writeSeedCache(array $urls): void
    {
        Config::set('xcity', 'seed_urls_json', json_encode($urls, JSON_UNESCAPED_UNICODE));
        Config::set('xcity', 'seed_urls_cached_at', Carbon::now()->toDateTimeString());
    }

    private function runningKey(string $seedKey): string
    {
        return "kana_{$seedKey}_running";
    }

    private function nextUrlKey(string $seedKey): string
    {
        return "kana_{$seedKey}_next_url";
    }

    private function pageKey(string $seedKey): string
    {
        return "kana_{$seedKey}_page";
    }

    private function completedAtKey(string $seedKey): string
    {
        return "kana_{$seedKey}_completed_at";
    }

    private function normalizeBloodType(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));
        if (!preg_match('/^(A|B|O|AB)\s+Type$/i', $normalized, $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    /**
     * @param  array<string, string>  $mappedFields
     * @param  array<string, string>  $rawFields
     * @return array<string, array{value: string, label?: string, raw_value?: string}>
     */
    private function buildProfileAttributes(array $mappedFields, array $rawFields): array
    {
        $attributes = [];
        foreach ($mappedFields as $kind => $value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            $attributes[$kind] = [
                'value' => trim($value),
            ];
        }

        $mappedLabels = [
            'date of birth',
            'blood type',
            'city of born',
            'height',
            'size',
            'hobby',
            'special skill',
            'other',
        ];

        foreach ($rawFields as $label => $value) {
            if (!is_string($label) || !is_string($value)) {
                continue;
            }

            $normalizedLabel = trim($label);
            $normalizedValue = trim($value);
            if ($normalizedLabel === '' || $normalizedValue === '') {
                continue;
            }

            if (in_array(mb_strtolower($normalizedLabel), $mappedLabels, true)) {
                continue;
            }

            $kind = 'raw.' . (preg_replace('/[^a-z0-9_]+/', '_', Str::lower($normalizedLabel)) ?? '');
            $kind = rtrim($kind, '_');

            if ($kind === 'raw.') {
                continue;
            }

            $attributes[$kind] = [
                'value' => $normalizedValue,
                'label' => $normalizedLabel,
                'raw_value' => $normalizedValue,
            ];
        }

        return $attributes;
    }

    private function normalizeBirthDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));

        try {
            return Carbon::createFromFormat('Y M d', $normalized)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
