<?php

namespace Modules\Udemy\Client\Dto\Interfaces;

interface IHasItemDto
{
    public function getId(): int;

    public function getClass(): string;
}
