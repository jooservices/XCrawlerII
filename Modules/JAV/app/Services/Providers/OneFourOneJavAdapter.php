<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Providers;

use Modules\JAV\Repositories\OneFourOneJavRepository;

final class OneFourOneJavAdapter extends AbstractBaseProvider
{
    public function __construct(
        private readonly OneFourOneJavRepository $repository,
    ) {
    }

    protected function repository(): object
    {
        return $this->repository;
    }
}
