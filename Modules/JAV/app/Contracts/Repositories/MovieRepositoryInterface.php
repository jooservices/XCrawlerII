<?php

declare(strict_types=1);

namespace Modules\JAV\Contracts\Repositories;

interface MovieRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return object Document model (FfJav|Onejav|OneFourOneJav)
     */
    public function upsertByCode(string $code, array $attributes): object;

    /**
     * @return object|null Document model or null
     */
    public function findByCode(string $code): ?object;
}
