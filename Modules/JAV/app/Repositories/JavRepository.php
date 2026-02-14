<?php

namespace Modules\JAV\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\JAV\Models\Jav;

class JavRepository
{
    public function query(): Builder
    {
        return Jav::query();
    }

    public function queryWithRelations(): Builder
    {
        return $this->query()->with(['actors', 'tags']);
    }

    public function loadRelations(Jav $jav): Jav
    {
        return $jav->load(['actors', 'tags']);
    }
}
