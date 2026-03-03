<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\DTOs;

use Modules\JAV\DTOs\TagDto;
use Modules\JAV\Tests\TestCase;

final class TagDtoTest extends TestCase
{
    public function test_constructor_with_required_name_only(): void
    {
        $dto = new TagDto(name: 'Drama');

        $this->assertSame('Drama', $dto->name);
        $this->assertNull($dto->description);
    }

    public function test_constructor_with_name_and_description(): void
    {
        $dto = new TagDto(name: 'School Girl', description: '制服テーマ');

        $this->assertSame('School Girl', $dto->name);
        $this->assertSame('制服テーマ', $dto->description);
    }

    public function test_constructor_with_empty_description(): void
    {
        $dto = new TagDto(name: 'Tag', description: '');

        $this->assertSame('Tag', $dto->name);
        $this->assertSame('', $dto->description);
    }
}
