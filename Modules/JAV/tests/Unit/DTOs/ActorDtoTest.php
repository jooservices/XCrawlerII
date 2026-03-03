<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\DTOs;

use Modules\JAV\DTOs\ActorDto;
use Modules\JAV\Tests\TestCase;

final class ActorDtoTest extends TestCase
{
    public function test_constructor_with_required_name_only(): void
    {
        $dto = new ActorDto(name: 'Yua Mikami');

        $this->assertSame('Yua Mikami', $dto->name);
        $this->assertNull($dto->avatar);
        $this->assertNull($dto->aliases);
        $this->assertNull($dto->birthDate);
        $this->assertNull($dto->birthplace);
        $this->assertNull($dto->bloodType);
        $this->assertNull($dto->height);
        $this->assertNull($dto->weight);
        $this->assertNull($dto->bust);
        $this->assertNull($dto->waist);
        $this->assertNull($dto->hip);
        $this->assertNull($dto->cupSize);
        $this->assertNull($dto->hobbies);
        $this->assertNull($dto->skills);
        $this->assertNull($dto->attributes);
        $this->assertNull($dto->crawledAt);
        $this->assertNull($dto->seenAt);
    }

    public function test_constructor_with_all_fields(): void
    {
        $birthDate = new \DateTimeImmutable('1992-03-01');
        $crawledAt = new \DateTimeImmutable('2025-01-01 12:00:00');

        $dto = new ActorDto(
            name: 'Actor Full',
            avatar: 'https://example.com/avatar.jpg',
            aliases: ['Alias1', 'Alias2'],
            birthDate: $birthDate,
            birthplace: 'Tokyo',
            bloodType: 'A',
            height: 160,
            weight: 50,
            bust: 90,
            waist: 60,
            hip: 88,
            cupSize: 'E',
            hobbies: ['Reading'],
            skills: ['Acting'],
            attributes: ['key' => 'value'],
            crawledAt: $crawledAt,
            seenAt: null,
        );

        $this->assertSame('Actor Full', $dto->name);
        $this->assertSame('https://example.com/avatar.jpg', $dto->avatar);
        $this->assertSame(['Alias1', 'Alias2'], $dto->aliases);
        $this->assertSame($birthDate, $dto->birthDate);
        $this->assertSame('Tokyo', $dto->birthplace);
        $this->assertSame('A', $dto->bloodType);
        $this->assertSame(160, $dto->height);
        $this->assertSame(50, $dto->weight);
        $this->assertSame(90, $dto->bust);
        $this->assertSame(60, $dto->waist);
        $this->assertSame(88, $dto->hip);
        $this->assertSame('E', $dto->cupSize);
        $this->assertSame(['Reading'], $dto->hobbies);
        $this->assertSame(['Acting'], $dto->skills);
        $this->assertSame(['key' => 'value'], $dto->attributes);
        $this->assertSame($crawledAt, $dto->crawledAt);
        $this->assertNull($dto->seenAt);
    }
}
