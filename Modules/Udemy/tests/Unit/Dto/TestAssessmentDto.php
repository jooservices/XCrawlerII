<?php

namespace Modules\Udemy\Tests\Unit\Dto;

use Modules\Udemy\Client\Dto\AssessmentDto;
use Modules\Udemy\Tests\TestCase;

class TestAssessmentDto extends TestCase
{
    final public function testGetId(): void
    {
        $dto = new AssessmentDto();
        $dto->transform([
            'id' => 1,
            'correct_response' => ['a', 'b', 'c'],
        ]);

        $this->assertEquals(1, $dto->getId());
    }

    final public function testGetCorrectResponse(): void
    {
        $dto = new AssessmentDto();
        $dto->transform([
            'id' => 1,
            'correct_response' => ['a', 'b', 'c'],
        ]);

        $this->assertEquals(['a', 'b', 'c'], $dto->getCorrectResponse());
    }

    final public function testInvalidField(): void
    {
        $dto = new AssessmentDto();
        $dto->transform([
            'id' => 1,
            'correct_response' => ['a', 'b', 'c'],
        ]);

        $this->assertNull($dto->invalid_field);
    }

    final public function testGetFields(): void
    {
        $dto = new AssessmentDto();
        $this->assertEquals(['id', 'correct_response'], $dto->getFields());
    }
}
