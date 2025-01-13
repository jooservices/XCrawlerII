<?php

namespace Modules\Udemy\Client\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;

trait TMe
{
    /**
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    final public function getCompletedIds(int $courseId): array
    {
        return $this->me()->progress($courseId)->getCompletedIds();
    }
}
