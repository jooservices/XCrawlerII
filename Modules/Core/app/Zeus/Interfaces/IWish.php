<?php

namespace Modules\Core\Zeus\Interfaces;

interface IWish
{
    public function wish(?\Closure $callback = null): bool;
}
