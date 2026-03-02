<?php

declare(strict_types=1);

namespace Modules\JAV\Services\Providers;

use Modules\JAV\Repositories\OnejavRepository;

final class OnejavAdapter extends AbstractBaseProvider
{
    public function __construct(
        private readonly OnejavRepository $repository,
    ) {
    }

    protected function repository(): object
    {
        return $this->repository;
    }
}
