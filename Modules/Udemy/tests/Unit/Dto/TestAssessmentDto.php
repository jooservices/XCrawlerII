<?php

namespace Modules\Udemy\Tests\Unit\Dto;

use Modules\Udemy\Client\Dto\AssessmentDto;
use Modules\Udemy\Tests\TestCase;

class TestAssessmentDto extends TestCase
{
    public function testGetId(): void
    {
        $dto = new AssessmentDto();
        $dto->transform([
            'id' => 1,
            'correct_response' => ['a', 'b', 'c'],
        ]);

        $this->assertEquals(1, $dto->getId());
    }

    public function testGetCorrectResponse(): void
    {
        $dto = new AssessmentDto();
        $dto->transform([
            'id' => 1,
            'correct_response' => ['a', 'b', 'c'],
        ]);

        $this->assertEquals(['a', 'b', 'c'], $dto->getCorrectResponse());
    }
}
