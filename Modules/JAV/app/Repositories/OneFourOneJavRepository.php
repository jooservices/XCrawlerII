<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\MongoDb\OneFourOneJav;

final class OneFourOneJavRepository
{
    /** @param  array<string, mixed>  $attributes */
    public function upsertByCode(string $code, array $attributes): OneFourOneJav
    {
        return OneFourOneJav::query()->updateOrCreate(
            ['code' => $code],
            array_merge(['code' => $code], $attributes)
        );
    }
}
