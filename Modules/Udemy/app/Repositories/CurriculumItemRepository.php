<?php

namespace Modules\Udemy\Repositories;

use Illuminate\Support\Facades\DB;

class CurriculumItemRepository
{
    final public function getTypes(): array
    {
        return DB::table('curriculum_items')
            ->select('type')
            ->groupBy('type')
            ->get()->toArray();
    }
}
