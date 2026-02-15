<?php

namespace Modules\JAV\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(public Model $model) {}
}
