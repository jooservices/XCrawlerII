<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\Movie;

final class MovieRepository
{
    /** @param  array<string, mixed>  $values */
    public function upsertByCode(string $code, array $values): Movie
    {
        return Movie::query()->updateOrCreate(
            ['code' => $code],
            $values
        );
    }

    public function findByCode(string $code): ?Movie
    {
        return Movie::query()->where('code', $code)->first();
    }
}
