<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Contracts\Repositories\MovieRepositoryInterface;
use Modules\JAV\Models\MongoDb\OneFourOneJav;

final class OneFourOneJavRepository implements MovieRepositoryInterface
{
    /** @param  array<string, mixed>  $attributes */
    public function upsertByCode(string $code, array $attributes): OneFourOneJav
    {
        return OneFourOneJav::query()->updateOrCreate(
            ['code' => $code],
            array_merge(['code' => $code], $attributes)
        );
    }

    public function findByCode(string $code): ?OneFourOneJav
    {
        return OneFourOneJav::query()->where('code', $code)->first();
    }
}
