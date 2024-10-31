<?php

namespace Modules\Jav\Repositories;

use Modules\Jav\Models\OnejavReference;

class OnejavRepository
{
    public function insert(array $data): OnejavReference
    {
        return OnejavReference::updateOrCreate([
            'url' => $data['url'],
            'dvd_id' => $data['dvd_id'],
        ], $data);
    }
}
