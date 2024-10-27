<?php

namespace Modules\Jav\Observers;

use Modules\Jav\Models\OnejavReference;

class OnejavReferenceObserver
{
    /**
     * Handle the OnejavReference "created" event.
     */
    public function created(OnejavReference $model): void
    {
        dd($model);
    }

    /**
     * Handle the OnejavReference "updated" event.
     */
    public function updated(OnejavReference $model): void
    {
        //
    }

    /**
     * Handle the OnejavReference "deleted" event.
     */
    public function deleted(OnejavReference $model): void
    {
        //
    }

    /**
     * Handle the OnejavReference "restored" event.
     */
    public function restored(OnejavReference $model): void
    {
        //
    }

    /**
     * Handle the OnejavReference "force deleted" event.
     */
    public function forceDeleted(OnejavReference $model): void
    {
        //
    }
}
