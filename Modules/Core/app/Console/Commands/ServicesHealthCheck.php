<?php

declare(strict_types=1);

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\Health;

class ServicesHealthCheck extends Command
{
    protected $signature = 'services:health';

    protected $description = 'Check health of required infrastructure services';

    public function handle(Health $health): int
    {
        $result = $health->check();

        $this->table(
            ['service', 'status', 'detail'],
            $result['checks']
        );

        return $result['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
