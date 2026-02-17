<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use Modules\Core\Facades\Config;
use Modules\JAV\Dtos\Item;
use Modules\JAV\Events\ProviderFetchCompleted;
use Modules\JAV\Events\ProviderFetchFailed;
use Modules\JAV\Events\ProviderFetchStarted;
use Modules\JAV\Events\TagsSyncCompleted;
use Modules\JAV\Events\TagsSyncFailed;
use Modules\JAV\Exceptions\CrawlerDelayException;
use Modules\JAV\Models\Tag;
use Modules\JAV\Services\Clients\FfjavClient;
use Modules\JAV\Services\CrawlerPaginationStateService;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Services\CrawlerStatusPolicyService;
use Modules\JAV\Services\Ffjav\ItemAdapter;
use Modules\JAV\Services\Ffjav\ItemsAdapter;
use Modules\JAV\Services\Ffjav\TagsAdapter;
use Symfony\Component\DomCrawler\Crawler;

class FfjavService
{
    public function __construct(
        protected FfjavClient $client,
        protected CrawlerResponseCacheService $cacheService,
        protected CrawlerPaginationStateService $paginationState,
        protected CrawlerStatusPolicyService $statusPolicy
    ) {}

    public function new(?int $page = null): ItemsAdapter
    {
        $isAutoMode = $page === null;
        $page = $page ?? $this->paginationState->getState('ffjav', 'new', 'new_page')['current_page'];

        $path = $page === 1 ? '/javtorrent' : '/javtorrent/page/'.$page;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'new', $path, $page);

        try {
            $response = $this->fetchResponse('ffjav', 'new', $path);
            $status = $response->status();

            if ($status >= 400) {
                return $this->handleFailure('ffjav', 'new', $path, $page, $status, false, $isAutoMode, 'new_page', $startedAt);
            }

            $items = app()->makeWith(ItemsAdapter::class, ['response' => $response]);
            $itemsDto = $items->items();
            $isEmpty = $itemsDto->items->count() === 0;

            if ($isAutoMode) {
                if ($isEmpty) {
                    $this->handleEmptyResult('ffjav', 'new', $path, $page, $startedAt, 'new_page');
                } else {
                    $this->paginationState->recordSuccess('ffjav', 'new', $items->currentPage(), $items->hasNextPage(), 'new_page');
                }
            }

            ProviderFetchCompleted::dispatch(
                'ffjav',
                'new',
                $path,
                $page,
                $items->currentPage(),
                $itemsDto->items->count(),
                $this->resolveNextPage('ffjav', 'new', $items->nextPage(), $isAutoMode, 'new_page'),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'ffjav',
                $items->currentPage()
            );

            return $items;
        } catch (CrawlerDelayException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
                'new',
                $path,
                $page,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function popular(?int $page = null): ItemsAdapter
    {
        $isAutoMode = $page === null;
        $page = $page ?? $this->paginationState->getState('ffjav', 'popular', 'popular_page')['current_page'];

        $path = $page === 1 ? '/popular' : '/popular/page/'.$page;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'popular', $path, $page);

        try {
            $response = $this->fetchResponse('ffjav', 'popular', $path);
            $status = $response->status();

            if ($status >= 400) {
                return $this->handleFailure('ffjav', 'popular', $path, $page, $status, false, $isAutoMode, 'popular_page', $startedAt);
            }

            $items = app()->makeWith(ItemsAdapter::class, ['response' => $response]);
            $itemsDto = $items->items();
            $isEmpty = $itemsDto->items->count() === 0;

            if ($isAutoMode) {
                if ($isEmpty) {
                    $this->handleEmptyResult('ffjav', 'popular', $path, $page, $startedAt, 'popular_page');
                } else {
                    $this->paginationState->recordSuccess('ffjav', 'popular', $items->currentPage(), $items->hasNextPage(), 'popular_page');
                }
            }

            ProviderFetchCompleted::dispatch(
                'ffjav',
                'popular',
                $path,
                $page,
                $items->currentPage(),
                $itemsDto->items->count(),
                $this->resolveNextPage('ffjav', 'popular', $items->nextPage(), $isAutoMode, 'popular_page'),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'ffjav',
                $items->currentPage()
            );

            return $items;
        } catch (CrawlerDelayException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
                'popular',
                $path,
                $page,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function daily(?string $date = null, ?int $page = null): ItemsAdapter
    {
        $date = $date
            ? Carbon::parse($date)->format('Y/m/d')
            : Carbon::now()->format('Y/m/d');

        $path = '/'.$date;
        if (($page ?? 1) > 1) {
            $path .= '/page/'.$page;
        }
        $effectivePage = $page ?? 1;
        $startedAt = microtime(true);
        ProviderFetchStarted::dispatch('ffjav', 'daily', $path, $effectivePage);

        try {
            $response = $this->fetchResponse('ffjav', 'daily', $path);
            $status = $response->status();

            if ($status >= 400) {
                return $this->handleFailure('ffjav', 'daily', $path, $effectivePage, $status, false, false, null, $startedAt);
            }

            $items = app()->makeWith(ItemsAdapter::class, ['response' => $response]);
            $itemsDto = $items->items();

            ProviderFetchCompleted::dispatch(
                'ffjav',
                'daily',
                $path,
                $effectivePage,
                $items->currentPage(),
                $itemsDto->items->count(),
                $items->nextPage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            \Modules\JAV\Events\ItemsFetched::dispatch(
                $itemsDto,
                'ffjav',
                $items->currentPage()
            );

            return $items;
        } catch (CrawlerDelayException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            ProviderFetchFailed::dispatch(
                'ffjav',
                'daily',
                $path,
                $effectivePage,
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }

    public function item(string $url): Item
    {
        $response = $this->fetchResponse('ffjav', 'item', $url);
        $crawler = new Crawler($response->toPsrResponse()->getBody()->getContents());

        return (new ItemAdapter($crawler))->getItem();
    }

    private function fetchResponse(string $provider, string $type, string $path)
    {
        $cached = $this->cacheService->getCachedResponse($provider, $type, $path);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->client->get($path);
        $this->cacheService->storeResponse($provider, $type, $path, $response->toPsrResponse());

        return $response;
    }

    private function handleFailure(
        string $provider,
        string $type,
        string $path,
        int $page,
        int $status,
        bool $isEmpty,
        bool $isAutoMode,
        ?string $legacyKey,
        float $startedAt
    ): ItemsAdapter {
        $policy = $this->statusPolicy->resolvePolicy($status, $isEmpty);
        $retryLimit = $this->resolveRetryLimit($provider);
        $jumpLimit = $this->resolveJumpLimit($provider);

        $result = $isAutoMode
            ? $this->paginationState->recordFailure($provider, $type, $page, $retryLimit, $jumpLimit, $policy['count_as_tail'], $legacyKey)
            : ['state' => ['current_page' => $page, 'current_page_failures' => 0, 'consecutive_skips' => 0], 'action' => 'advance'];

        ProviderFetchFailed::dispatch(
            $provider,
            $type,
            $path,
            $page,
            "HTTP {$status}",
            (int) round((microtime(true) - $startedAt) * 1000)
        );

        if (in_array($policy['action'], ['retry', 'cooldown'], true) && $result['action'] === 'retry_same') {
            $delay = max(0, (int) $policy['delay_sec']);
            $message = "HTTP {$status}";
            throw $policy['action'] === 'cooldown'
                ? CrawlerDelayException::forCooldown($delay, $message)
                : CrawlerDelayException::forRetry($delay, $message);
        }

        return $this->emptyItems();
    }

    private function handleEmptyResult(
        string $provider,
        string $type,
        string $path,
        int $page,
        float $startedAt,
        ?string $legacyKey
    ): void {
        $policy = $this->statusPolicy->resolvePolicy(200, true);
        $retryLimit = $this->resolveRetryLimit($provider);
        $jumpLimit = $this->resolveJumpLimit($provider);

        $result = $this->paginationState->recordFailure($provider, $type, $page, $retryLimit, $jumpLimit, $policy['count_as_tail'], $legacyKey);

        ProviderFetchFailed::dispatch(
            $provider,
            $type,
            $path,
            $page,
            'Empty page response',
            (int) round((microtime(true) - $startedAt) * 1000)
        );

        if (in_array($policy['action'], ['retry', 'cooldown'], true) && $result['action'] === 'retry_same') {
            $delay = max(0, (int) $policy['delay_sec']);
            throw $policy['action'] === 'cooldown'
                ? CrawlerDelayException::forCooldown($delay, 'Empty page response')
                : CrawlerDelayException::forRetry($delay, 'Empty page response');
        }
    }

    private function resolveNextPage(string $provider, string $type, int $fallback, bool $isAutoMode, ?string $legacyKey): int
    {
        if (! $isAutoMode) {
            return $fallback;
        }

        return $this->paginationState->getState($provider, $type, $legacyKey)['current_page'];
    }

    private function resolveRetryLimit(string $provider): int
    {
        $value = (int) Config::get($provider, 'page_retry_limit', 3);

        return $value > 0 ? $value : 3;
    }

    private function resolveJumpLimit(string $provider): int
    {
        $value = (int) Config::get($provider, 'page_jump_limit', 3);

        return $value > 0 ? $value : 3;
    }

    private function emptyItems(): ItemsAdapter
    {
        $emptyResponse = new \JOOservices\Client\Response\ResponseWrapper(new \GuzzleHttp\Psr7\Response(200, [], ''));

        return app()->makeWith(ItemsAdapter::class, ['response' => $emptyResponse]);
    }

    public function tags(): \Illuminate\Support\Collection
    {
        $startedAt = microtime(true);

        try {
            $response = $this->client->get('/tag');
            $tags = (new TagsAdapter($response))->tags();

            $tagNames = $tags
                ->map(fn ($name) => trim($name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();
            $existingTags = Tag::whereIn('name', $tagNames)->pluck('id', 'name');
            $missingTags = $tagNames->diff($existingTags->keys());
            if ($missingTags->isNotEmpty()) {
                $toInsert = $missingTags->map(fn ($name) => ['name' => $name])->all();
                Tag::insertOrIgnore($toInsert);
            }

            TagsSyncCompleted::dispatch(
                'ffjav',
                $tagNames->count(),
                $missingTags->count(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            return $tags;
        } catch (\Throwable $exception) {
            TagsSyncFailed::dispatch(
                'ffjav',
                $exception->getMessage(),
                (int) round((microtime(true) - $startedAt) * 1000)
            );

            throw $exception;
        }
    }
}
