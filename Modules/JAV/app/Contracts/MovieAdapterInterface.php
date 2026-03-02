<?php

declare(strict_types=1);

namespace Modules\JAV\Contracts;

use Modules\JAV\DTOs\MovieDto;

interface MovieAdapterInterface
{
    /**
     * Persist movie snapshot (e.g. to source-specific Mongo collection).
     * Return void: caller does not need the persisted document.
     * If you need the persisted model later, consider changing to return Modules\Core\Models\MongoDb.
     */
    public function save(MovieDto $dto): void;
}
