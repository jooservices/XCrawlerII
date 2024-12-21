<?php

namespace Modules\Core\Tests\Unit\Dto;

use Modules\Core\Dto\BaseDto;
use Modules\Core\Exceptions\InvalidDtoDataException;
use Modules\Core\Tests\TestCase;

class BaseDtoTest extends TestCase
{
    final public function testValidBaseDto(): void
    {
        $baseDto = (new BaseDto())->transform([
            'id' => 1,
            'name' => 'Test',
            'price' => 10.5,
            'is_active' => true,
            'data' => [
                'id' => 1,
                'name' => 'Test',
                'price' => 10.5,
                'is_active' => true,
            ],
        ]);

        $this->assertEquals(1, $baseDto->getInt('id'));
        $this->assertEquals('Test', $baseDto->getString('name'));
        $this->assertEquals(10.5, $baseDto->getFloat('price'));
        $this->assertTrue($baseDto->getBool('is_active'));
        $this->assertEquals(1, $baseDto->getObject('data')->id);
        $this->assertEquals('Test', $baseDto->getObject('data')->name);
        $this->assertEquals(10.5, $baseDto->getObject('data')->price);
        $this->assertTrue($baseDto->getObject('data')->is_active);
    }

    final public function testInvalidBaseDto(): void
    {
        $this->expectException(InvalidDtoDataException::class);
        (new BaseDto())->transform(null);
    }
}
