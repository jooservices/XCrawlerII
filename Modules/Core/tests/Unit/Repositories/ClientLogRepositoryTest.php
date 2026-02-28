<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Repositories;

use InvalidArgumentException;
use Modules\Core\Models\MongoDb\ClientLog;
use Modules\Core\Repositories\ClientLogRepository;
use Modules\Core\Tests\TestCase;

final class ClientLogRepositoryTest extends TestCase
{
    private ClientLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ClientLogRepository();
        $this->cleanCollection();
    }

    protected function tearDown(): void
    {
        $this->cleanCollection();
        parent::tearDown();
    }

    public function test_happy_create_persists_client_log_document(): void
    {
        $attributes = ClientLog::factory()->make()->getAttributes();

        $model = $this->repository->create($attributes);

        $this->assertInstanceOf(ClientLog::class, $model);
        $this->assertSame($attributes['url'], $model->url);
        $this->assertDatabaseHas(ClientLog::COLLECTION, [
            'id' => $model->getKey(),
        ], 'mongodb');
    }

    public function test_unhappy_create_throws_when_required_fields_are_missing(): void
    {
        $attributes = ClientLog::factory()->make()->getAttributes();
        unset($attributes['url']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required ClientLog attribute: url');

        $this->repository->create($attributes);
    }

    public function test_edge_create_accepts_max_length_url_and_path_values(): void
    {
        $attributes = ClientLog::factory()->make()->getAttributes();
        $attributes['path'] = '/' . str_repeat('segment-', 128);
        $attributes['url'] = 'https://example.test/' . str_repeat('q', 1024);

        $model = $this->repository->create($attributes);

        $this->assertSame($attributes['path'], $model->path);
        $this->assertSame($attributes['url'], $model->url);
    }

    public function test_security_create_persists_xss_like_url_as_data_without_execution_side_effects(): void
    {
        $attributes = ClientLog::factory()->make()->getAttributes();
        $attributes['url'] = 'https://example.test/callback?next=%3Cscript%3Ealert(1)%3C%2Fscript%3E';

        $model = $this->repository->create($attributes);

        $this->assertSame($attributes['url'], $model->url);
    }

    private function cleanCollection(): void
    {
        try {
            ClientLog::query()->delete();
        } catch (\Throwable) {
            // ignore
        }
    }
}
