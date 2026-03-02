<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Providers;

use Modules\JAV\Repositories\FfJavRepository;

final class FfJavAdapter extends AbstractBaseProvider
{
    public function __construct(
        private readonly FfJavRepository $repository,
    ) {
    }

    protected function repository(): object
    {
        return $this->repository;
    }
}
