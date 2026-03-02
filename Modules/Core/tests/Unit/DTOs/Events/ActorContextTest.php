<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\DTOs\Events;

use Modules\Core\DTOs\Events\ActorContext;
use Modules\Core\Enums\Events\ActorType;
use Modules\Core\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ActorContextTest extends TestCase
{
    #[Test]
    public function constructor_sets_all_properties(): void
    {
        $actor = new ActorContext(
            actorType: ActorType::User,
            actorId: 'user-1',
            correlationId: 'corr-1',
            requestId: 'req-1',
            ip: '127.0.0.1',
            userAgent: 'TestAgent/1.0'
        );

        $this->assertSame(ActorType::User, $actor->actorType);
        $this->assertSame('user-1', $actor->actorId);
        $this->assertSame('corr-1', $actor->correlationId);
        $this->assertSame('req-1', $actor->requestId);
        $this->assertSame('127.0.0.1', $actor->ip);
        $this->assertSame('TestAgent/1.0', $actor->userAgent);
    }

    #[Test]
    public function system_returns_system_actor_with_optional_correlation_id(): void
    {
        $actor = ActorContext::system();

        $this->assertSame(ActorType::System, $actor->actorType);
        $this->assertNull($actor->actorId);
        $this->assertNull($actor->correlationId);
    }

    #[Test]
    public function system_with_correlation_id_sets_correlation_id(): void
    {
        $actor = ActorContext::system('corr-xyz');

        $this->assertSame(ActorType::System, $actor->actorType);
        $this->assertNull($actor->actorId);
        $this->assertSame('corr-xyz', $actor->correlationId);
    }

    #[Test]
    public function user_returns_user_actor_with_given_id_and_optional_correlation_id(): void
    {
        $actor = ActorContext::user('user-99');

        $this->assertSame(ActorType::User, $actor->actorType);
        $this->assertSame('user-99', $actor->actorId);
        $this->assertNull($actor->correlationId);
    }

    #[Test]
    public function user_with_correlation_id_sets_correlation_id(): void
    {
        $actor = ActorContext::user('user-42', 'corr-abc');

        $this->assertSame(ActorType::User, $actor->actorType);
        $this->assertSame('user-42', $actor->actorId);
        $this->assertSame('corr-abc', $actor->correlationId);
    }
}
