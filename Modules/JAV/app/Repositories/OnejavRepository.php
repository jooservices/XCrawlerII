<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\MongoDb\Onejav;

final class OnejavRepository
{
    /** @param  array<string, mixed>  $attributes */
    public function upsertByCode(string $code, array $attributes): Onejav
    {
        return Onejav::query()->updateOrCreate(
            ['code' => $code],
            array_merge(['code' => $code], $attributes)
        );
    }
}
