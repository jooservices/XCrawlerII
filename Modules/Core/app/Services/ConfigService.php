<?php

namespace Modules\Core\Services;

use Modules\Core\Contracts\ConfigRepositoryInterface;
use Modules\Core\Models\MongoDb\Config;

class ConfigService
{
    public function __construct(
        private ConfigRepositoryInterface $repository
    ) {}

    /**
     * Get a config value.
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $config = $this->repository->get($group, $key);

        return $config ? $config->value : $default;
    }

    /**
     * Set a config value.
     */
    public function set(string $group, string $key, mixed $value, ?string $description = null): Config
    {
        return $this->repository->updateOrCreate(
            $group,
            $key,
            $value,
            $description
        );
    }
}
