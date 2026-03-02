<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\MongoDb\FfJav;

final class FfJavRepository
{
    /** @param  array<string, mixed>  $attributes */
    public function upsertByCode(string $code, array $attributes): FfJav
    {
        return FfJav::query()->updateOrCreate(
            ['code' => $code],
            array_merge(['code' => $code], $attributes)
        );
    }
}
