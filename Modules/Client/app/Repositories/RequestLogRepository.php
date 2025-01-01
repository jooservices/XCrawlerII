<?php

namespace Modules\Client\Repositories;

use Modules\Client\Models\RequestLog;

class RequestLogRepository
{
    final public function cleanup(): void
    {
        RequestLog::where('created_at', '<', now()
            ->where('status_code', '<=', 299)
            ->subDays(7))->delete();
    }
}
