<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services;

use Mockery;
use Modules\Core\Models\MongoDb\Config;
use Modules\Core\Repositories\Contracts\ConfigRepositoryInterface;
use Modules\Core\Services\ConfigService;
use Modules\Core\Tests\TestCase;

class ConfigServiceTest extends TestCase
{
    private $repositoryMock;
    private ConfigService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(ConfigRepositoryInterface::class);
        $this->service = new ConfigService($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_returns_value_when_found(): void
    {
        $config = new Config(['value' => 'test_value']);

        $this->repositoryMock->shouldReceive('get')
            ->once()
            ->with('group1', 'key1')
            ->andReturn($config);

        $result = $this->service->get('group1', 'key1');

        $this->assertEquals('test_value', $result);
    }

    public function test_get_returns_default_when_not_found(): void
    {
        $this->repositoryMock->shouldReceive('get')
            ->once()
            ->with('group1', 'key1')
            ->andReturn(null);

        $result = $this->service->get('group1', 'key1', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_set_delegates_to_repository(): void
    {
        $config = new Config(['group' => 'g1', 'key' => 'k1', 'value' => 'v1']);

        $this->repositoryMock->shouldReceive('updateOrCreate')
            ->once()
            ->with('g1', 'k1', 'v1', 'desc1')
            ->andReturn($config);

        $result = $this->service->set('g1', 'k1', 'v1', 'desc1');

        $this->assertSame($config, $result);
    }
}
